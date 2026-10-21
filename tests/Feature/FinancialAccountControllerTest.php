<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\FinancialAccount;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class FinancialAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_routes_require_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('accounts.index'))->assertRedirect(route('login'));
        $this->post(route('accounts.store'))->assertRedirect(route('login'));
    }

    public function test_dashboard_and_account_list_show_only_owned_financial_accounts(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor, ['name' => 'Conta operacional']);
        $this->createAccount(User::factory()->create(), ['name' => 'Conta reserva']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Conta operacional')
            ->assertDontSee('Conta reserva');

        $this->get(route('accounts.index'))
            ->assertOk()
            ->assertSee($account->name)
            ->assertDontSee('Conta reserva');
    }

    public function test_editor_can_create_account_with_exact_initial_balance(): void
    {
        $this->signIn(User::ROLE_EDITOR);

        $this->post(route('accounts.store'), [
            'name' => 'Conta corrente',
            'bank_name' => 'Banco Central',
            'branch' => '0001',
            'account_number' => '12345-6',
            'type' => 'checking',
            'currency' => 'BRL',
            'opening_balance' => '1000.25',
        ])->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success');

        $account = FinancialAccount::query()->where('account_number', '12345-6')->firstOrFail();

        $this->assertSame(100025, (int) $account->opening_balance_minor);
        $this->assertSame('1000.25', $account->current_balance);
        $this->assertSame('BRL', $account->currency);
    }

    public function test_editor_can_update_account_details_but_not_opening_balance(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor, [
            'opening_balance_minor' => 12345,
        ]);

        $this->put(route('accounts.update', $account), [
            'name' => 'Conta renomeada',
            'bank_name' => $account->bank_name,
            'branch' => $account->branch,
            'account_number' => $account->account_number,
            'type' => $account->type,
            'currency' => 'USD',
            'opening_balance' => '999999.99',
            'is_active' => '1',
        ])->assertRedirect(route('accounts.show', $account));

        $this->assertDatabaseHas('financial_accounts', [
            'id' => $account->id,
            'name' => 'Conta renomeada',
            'currency' => 'BRL',
            'opening_balance_minor' => 12345,
        ]);
    }

    public function test_opening_balance_cannot_be_changed_after_account_creation(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor, ['opening_balance_minor' => 12345]);

        $this->expectException(LogicException::class);
        $account->opening_balance_minor = 99999;
        $account->save();
    }

    public function test_transactions_cannot_be_updated_or_deleted(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor);
        $transaction = $account->transactions()->create([
            'type' => AccountTransaction::TYPE_CREDIT,
            'amount_minor' => 1000,
            'description' => 'Crédito original',
        ]);

        try {
            $transaction->description = 'Alteração indevida';
            $transaction->save();
            $this->fail('An existing transaction must not be editable.');
        } catch (LogicException $exception) {
            $this->assertSame('Account transactions are immutable; post a reversal instead.', $exception->getMessage());
        }

        try {
            $transaction->delete();
            $this->fail('An existing transaction must not be deletable.');
        } catch (LogicException $exception) {
            $this->assertSame('Account transactions cannot be deleted; post a reversal instead.', $exception->getMessage());
        }

        $this->assertDatabaseHas('account_transactions', [
            'id' => $transaction->id,
            'description' => 'Crédito original',
        ]);
    }

    public function test_audit_fields_are_not_mass_assignable(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor);
        $transaction = $account->transactions()->create([
            'type' => AccountTransaction::TYPE_CREDIT,
            'amount_minor' => 1000,
            'description' => 'Crédito',
            'posted_by' => $editor->id,
            'reversal_of_id' => 999,
        ]);

        $this->assertNull($transaction->posted_by);
        $this->assertNull($transaction->reversal_of_id);
    }

    public function test_editor_can_record_and_reverse_a_ledger_entry_only_once(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor, ['opening_balance_minor' => 50000]);

        $this->post(route('accounts.transactions.store', $account), [
            'type' => AccountTransaction::TYPE_CREDIT,
            'amount' => '125.75',
            'description' => 'Depósito',
        ])->assertRedirect(route('accounts.show', $account));

        $original = $account->transactions()->firstOrFail();
        $this->assertSame(62575, $account->fresh()->current_balance_minor);
        $this->assertSame($editor->id, $original->posted_by);

        $this->post(route('accounts.transactions.reverse', [$account, $original]), [
            'reason' => 'Lançamento feito por engano',
        ])->assertRedirect(route('accounts.show', $account));

        $this->assertSame(50000, $account->fresh()->current_balance_minor);
        $this->assertDatabaseCount('account_transactions', 2);

        $this->post(route('accounts.transactions.reverse', [$account, $original]), [
            'reason' => 'Tentativa duplicada',
        ])->assertSessionHasErrors('transaction');

        $this->assertDatabaseCount('account_transactions', 2);
    }

    public function test_viewer_cannot_read_or_change_accounts_without_a_share(): void
    {
        $accountOwner = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $account = $this->createAccount($accountOwner, ['name' => 'Conta para leitura']);
        $viewer = $this->signIn(User::ROLE_VIEWER);

        $this->get(route('accounts.index'))->assertOk()->assertDontSee($account->name);
        $this->get(route('accounts.show', $account))->assertForbidden();
        $this->get(route('accounts.create'))->assertForbidden();
        $this->post(route('accounts.store'), [
            'name' => 'Conta proibida',
            'bank_name' => 'Banco',
            'account_number' => '888',
            'type' => 'checking',
            'currency' => 'BRL',
            'opening_balance' => '0',
        ])->assertForbidden();
        $this->get(route('accounts.edit', $account))->assertForbidden();
        $this->put(route('accounts.update', $account), [
            'name' => 'Modificada por visualizador',
            'bank_name' => $account->bank_name,
            'branch' => $account->branch,
            'account_number' => $account->account_number,
            'type' => $account->type,
            'is_active' => '1',
        ])->assertForbidden();
        $this->post(route('accounts.transactions.store', $account), [
            'type' => 'debit',
            'amount' => '1.00',
            'description' => 'Tentativa não permitida',
        ])->assertForbidden();

        $this->assertSame(User::ROLE_VIEWER, $viewer->role);
        $this->assertSame('0.00', $account->fresh()->current_balance);
    }

    public function test_archived_accounts_reject_new_transactions(): void
    {
        $editor = $this->signIn(User::ROLE_EDITOR);
        $account = $this->createAccount($editor, ['is_active' => false]);

        $this->post(route('accounts.transactions.store', $account), [
            'type' => 'credit',
            'amount' => '1.00',
            'description' => 'Depósito em conta arquivada',
        ])->assertSessionHasErrors('account');

        $this->assertDatabaseCount('account_transactions', 0);
    }

    private function signIn(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        return $user;
    }

    private function createAccount(User $creator, array $attributes = []): FinancialAccount
    {
        $attributes = array_merge([
            'name' => 'Conta principal',
            'bank_name' => 'Banco Exemplo',
            'branch' => '0001',
            'account_number' => (string) fake()->unique()->numerify('########'),
            'type' => 'checking',
            'currency' => 'BRL',
            'opening_balance_minor' => Money::toMinorUnits('0.00'),
            'is_active' => true,
        ], $attributes);

        $openingBalance = $attributes['opening_balance_minor'];
        unset($attributes['opening_balance_minor']);

        $account = $creator->accounts()->make($attributes);
        $account->opening_balance_minor = $openingBalance;
        $account->save();

        return $account;
    }
}
