<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\FinancialAccount;
use App\Models\User;
use App\Models\UserShare;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserShareControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_grant_view_access_and_revoke_it_without_allowing_chain_sharing(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $recipient = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $thirdUser = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $otherOwner = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $account = $this->createAccount($owner, 'Conta compartilhada');
        $this->createAccount($otherOwner, 'Conta privada');
        $transaction = $account->transactions()->create([
            'type' => AccountTransaction::TYPE_CREDIT,
            'amount_minor' => 12000,
            'description' => 'Receita compartilhada',
        ]);

        $this->actingAs($owner)
            ->get(route('sharing.index'))
            ->assertOk()
            ->assertSee('Acessos concedidos por você');

        $this->post(route('sharing.store'), [
            'recipient_id' => $recipient->id,
            'permission' => UserShare::PERMISSION_VIEW,
        ])->assertRedirect(route('sharing.index'));

        $share = UserShare::query()->where('owner_id', $owner->id)->firstOrFail();
        $this->get(route('sharing.index'))
            ->assertOk()
            ->assertSee($recipient->name)
            ->assertSee($owner->name);
        $this->assertSame($owner->id, $share->granted_by_id);
        $this->assertSame(UserShare::STATUS_ACTIVE, $share->status);
        $this->assertNotNull($share->granted_at);

        $this->actingAs($recipient)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Conta compartilhada')
            ->assertDontSee('Conta privada');
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Conta compartilhada')
            ->assertDontSee('Receita compartilhada');
        $this->get(route('accounts.show', $account))
            ->assertOk()
            ->assertSee('Receita compartilhada');
        $this->post(route('accounts.transactions.store', $account), [
            'type' => AccountTransaction::TYPE_CREDIT,
            'amount' => '1.00',
            'description' => 'Lançamento não permitido',
        ])->assertForbidden();

        $this->createAccount($recipient, 'Conta própria do convidado');
        $this->post(route('sharing.store'), [
            'recipient_id' => $thirdUser->id,
            'permission' => UserShare::PERMISSION_VIEW,
        ])->assertRedirect(route('sharing.index'));

        $this->actingAs($thirdUser)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Conta própria do convidado')
            ->assertDontSee('Conta compartilhada');
        $this->assertDatabaseHas('account_transactions', [
            'id' => $transaction->id,
            'financial_account_id' => $account->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('sharing.destroy', $share))
            ->assertRedirect(route('sharing.index'));

        $this->assertDatabaseHas('user_shares', [
            'id' => $share->id,
            'status' => UserShare::STATUS_REVOKED,
        ]);
        $this->actingAs($recipient->fresh())
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertDontSee('Conta compartilhada');
        $this->get(route('accounts.show', $account))->assertForbidden();
    }

    public function test_owner_can_upgrade_view_access_to_edit_and_shared_editor_can_change_data(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $recipient = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $account = $this->createAccount($owner, 'Conta para edição');

        $this->actingAs($owner)->post(route('sharing.store'), [
            'recipient_id' => $recipient->id,
            'permission' => UserShare::PERMISSION_VIEW,
        ])->assertRedirect(route('sharing.index'));
        $share = UserShare::query()->firstOrFail();

        $this->actingAs($recipient)->put(route('accounts.update', $account), [
            'name' => 'Alteração antes da permissão',
            'bank_name' => $account->bank_name,
            'branch' => $account->branch,
            'account_number' => $account->account_number,
            'type' => $account->type,
            'is_active' => '1',
        ])->assertForbidden();

        $this->actingAs($owner)->put(route('sharing.update', $share), [
            'permission' => UserShare::PERMISSION_EDIT,
        ])->assertRedirect(route('sharing.index'));

        $this->assertDatabaseHas('user_shares', [
            'id' => $share->id,
            'permission' => UserShare::PERMISSION_EDIT,
            'granted_by_id' => $owner->id,
        ]);

        $this->actingAs($recipient)->put(route('accounts.update', $account), [
            'name' => 'Conta atualizada pelo convidado',
            'bank_name' => $account->bank_name,
            'branch' => $account->branch,
            'account_number' => $account->account_number,
            'type' => $account->type,
            'is_active' => '1',
        ])->assertRedirect(route('accounts.show', $account));

        $this->post(route('accounts.transactions.store', $account), [
            'type' => AccountTransaction::TYPE_DEBIT,
            'amount' => '25.00',
            'description' => 'Despesa autorizada',
        ])->assertRedirect(route('accounts.show', $account));

        $this->assertDatabaseHas('financial_accounts', [
            'id' => $account->id,
            'name' => 'Conta atualizada pelo convidado',
        ]);
        $this->assertDatabaseHas('account_transactions', [
            'financial_account_id' => $account->id,
            'posted_by' => $recipient->id,
            'description' => 'Despesa autorizada',
        ]);
    }

    public function test_user_cannot_revoke_another_owners_share_or_share_with_themself(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $recipient = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($owner)->post(route('sharing.store'), [
            'recipient_id' => $recipient->id,
            'permission' => UserShare::PERMISSION_VIEW,
        ])->assertRedirect(route('sharing.index'));
        $share = UserShare::query()->firstOrFail();

        $this->actingAs($recipient)
            ->delete(route('sharing.destroy', $share))
            ->assertNotFound();
        $this->assertDatabaseHas('user_shares', [
            'id' => $share->id,
            'status' => UserShare::STATUS_ACTIVE,
        ]);

        $this->actingAs($owner)->post(route('sharing.store'), [
            'recipient_id' => $owner->id,
            'permission' => UserShare::PERMISSION_VIEW,
        ])->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('user_shares', 1);
    }

    public function test_sharing_page_requires_authentication(): void
    {
        $this->get(route('sharing.index'))->assertRedirect(route('login'));
        $this->post(route('sharing.store'))->assertRedirect(route('login'));
    }

    private function createAccount(User $owner, string $name): FinancialAccount
    {
        $account = $owner->accounts()->make([
            'name' => $name,
            'bank_name' => 'Banco de teste',
            'branch' => '0001',
            'account_number' => Str::random(12),
            'type' => 'checking',
            'currency' => 'BRL',
            'is_active' => true,
        ]);
        $account->opening_balance_minor = 0;
        $account->save();

        return $account;
    }
}
