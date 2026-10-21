<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\FinancialAccount;
use App\Models\Revenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RevenueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_a_prediction_without_an_income_source(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->actingAs($editor);
        $this->get(route('revenues.create'))
            ->assertOk()
            ->assertSee('O sistema não criará previsões futuras automaticamente.');

        $this->post(route('revenues.store'), [
            'name' => 'Consultoria',
            'amount' => '1234.50',
            'expected_date' => '2026-11-15',
            'currency' => 'BRL',
            'source' => '',
            'notes' => '',
        ])->assertRedirect();

        $revenue = Revenue::query()->firstOrFail();
        $this->assertSame($editor->id, $revenue->user_id);
        $this->assertSame(123450, (int) $revenue->amount_minor);
        $this->assertSame(Revenue::STATUS_PENDING, $revenue->status);
        $this->assertNull($revenue->source);
        $this->assertSame('2026-11-15', $revenue->expected_date->toDateString());
        $this->assertDatabaseCount('account_transactions', 0);
        $this->assertDatabaseHas('revenue_histories', [
            'revenue_id' => $revenue->id,
            'changed_by' => $editor->id,
            'action' => 'created',
        ]);

        $this->get(route('revenues.show', $revenue))
            ->assertOk()
            ->assertSee('Pendente');
    }

    public function test_editor_can_receive_a_prediction_to_an_active_account_once(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $account = $this->createAccount($editor);
        $revenue = $this->createRevenue($editor, [
            'amount_minor' => 12575,
            'name' => 'Pagamento de projeto',
        ]);
        $this->actingAs($editor);

        $this->post(route('revenues.receive', $revenue), [
            'financial_account_id' => $account->id,
        ])->assertRedirect(route('revenues.show', $revenue));

        $revenue->refresh();
        $transaction = $revenue->accountTransaction;
        $this->assertSame(Revenue::STATUS_RECEIVED, $revenue->status);
        $this->assertSame($account->id, $revenue->financial_account_id);
        $this->assertNotNull($revenue->received_at);
        $this->assertSame(AccountTransaction::TYPE_CREDIT, $transaction->type);
        $this->assertSame(12575, (int) $transaction->amount_minor);
        $this->assertSame($editor->id, $transaction->posted_by);
        $this->assertSame(12575, $account->fresh()->current_balance_minor);
        $this->assertDatabaseHas('revenue_histories', [
            'revenue_id' => $revenue->id,
            'action' => 'received',
        ]);

        $this->post(route('revenues.receive', $revenue), [
            'financial_account_id' => $account->id,
        ])->assertSessionHasErrors('revenue');

        $this->assertDatabaseCount('account_transactions', 1);
    }

    public function test_received_revenue_amount_edits_reverse_the_old_credit_and_post_a_new_one(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $account = $this->createAccount($editor);
        $revenue = $this->createRevenue($editor, [
            'amount_minor' => 10000,
            'name' => 'Pagamento',
        ]);
        $this->actingAs($editor);
        $this->post(route('revenues.receive', $revenue), [
            'financial_account_id' => $account->id,
        ])->assertRedirect();
        $original = $revenue->fresh()->accountTransaction;

        $this->put(route('revenues.update', $revenue), [
            'name' => 'Pagamento revisado',
            'amount' => '150.00',
            'expected_date' => '2026-11-15',
            'currency' => 'BRL',
            'source' => 'Cliente',
            'notes' => 'Valor atualizado',
        ])->assertRedirect(route('revenues.show', $revenue));

        $revenue->refresh();
        $this->assertSame('Pagamento revisado', $revenue->name);
        $this->assertSame('150.00', $revenue->amount);
        $this->assertSame(15000, $account->fresh()->current_balance_minor);
        $this->assertSame(3, AccountTransaction::query()->count());
        $this->assertDatabaseHas('account_transactions', [
            'reversal_of_id' => $original->id,
            'type' => AccountTransaction::TYPE_DEBIT,
            'amount_minor' => 10000,
        ]);
        $this->assertSame(15000, (int) $revenue->accountTransaction->amount_minor);
        $this->assertDatabaseHas('revenue_histories', [
            'revenue_id' => $revenue->id,
            'action' => 'updated',
        ]);
    }

    public function test_received_revenue_can_be_cancelled_only_through_revenue_actions(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $account = $this->createAccount($editor);
        $revenue = $this->createRevenue($editor, ['amount_minor' => 10000]);
        $this->actingAs($editor);
        $this->post(route('revenues.receive', $revenue), [
            'financial_account_id' => $account->id,
        ])->assertRedirect();
        $transaction = $revenue->fresh()->accountTransaction;

        $this->post(route('accounts.transactions.reverse', [$account, $transaction]), [
            'reason' => 'Cancelamento indevido',
        ])->assertSessionHasErrors('transaction');

        $this->post(route('revenues.cancel', $revenue))
            ->assertRedirect(route('revenues.show', $revenue));

        $revenue->refresh();
        $this->assertSame(Revenue::STATUS_CANCELLED, $revenue->status);
        $this->assertSame(0, $account->fresh()->current_balance_minor);
        $this->assertDatabaseHas('account_transactions', [
            'reversal_of_id' => $transaction->id,
            'type' => AccountTransaction::TYPE_DEBIT,
            'amount_minor' => 10000,
        ]);
        $this->assertDatabaseHas('revenue_histories', [
            'revenue_id' => $revenue->id,
            'action' => 'cancelled',
        ]);
    }

    public function test_editor_can_cancel_a_pending_prediction_without_posting_a_transaction(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $revenue = $this->createRevenue($editor);
        $this->actingAs($editor);

        $this->post(route('revenues.cancel', $revenue))
            ->assertRedirect(route('revenues.show', $revenue));

        $this->assertSame(Revenue::STATUS_CANCELLED, $revenue->fresh()->status);
        $this->assertDatabaseCount('account_transactions', 0);
        $this->assertDatabaseHas('revenue_histories', [
            'revenue_id' => $revenue->id,
            'action' => 'cancelled',
        ]);
    }

    public function test_deleting_a_received_revenue_keeps_its_ledger_entry_and_audit_history(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $account = $this->createAccount($editor);
        $revenue = $this->createRevenue($editor, ['amount_minor' => 9800]);
        $this->actingAs($editor);
        $this->post(route('revenues.receive', $revenue), [
            'financial_account_id' => $account->id,
        ])->assertRedirect();
        $transaction = $revenue->fresh()->accountTransaction;

        $this->delete(route('revenues.destroy', $revenue))
            ->assertRedirect(route('revenues.index'));

        $this->assertSoftDeleted('revenues', ['id' => $revenue->id]);
        $this->assertModelExists($transaction->fresh());
        $this->assertSame(9800, $account->fresh()->current_balance_minor);
        $this->get(route('revenues.history'))
            ->assertOk()
            ->assertSee('Excluída')
            ->assertSee('Receita de teste');
    }

    public function test_users_cannot_view_or_change_another_users_revenue(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $revenue = $this->createRevenue($owner);
        $otherUser = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->actingAs($otherUser);

        $this->get(route('revenues.show', $revenue))->assertForbidden();
        $this->put(route('revenues.update', $revenue), [])->assertForbidden();
        $this->delete(route('revenues.destroy', $revenue))->assertForbidden();
    }

    public function test_viewer_cannot_create_a_revenue(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $this->actingAs($viewer);

        $this->post(route('revenues.store'), [
            'name' => 'Receita não autorizada',
            'amount' => '10.00',
            'expected_date' => '2026-11-15',
            'currency' => 'BRL',
        ])->assertForbidden();

        $this->assertDatabaseCount('revenues', 0);
    }

    public function test_invalid_amount_is_rejected_without_creating_history_or_revenue(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $this->actingAs($editor);

        $this->from(route('revenues.create'))
            ->post(route('revenues.store'), [
                'name' => 'Receita inválida',
                'amount' => '0.00',
                'expected_date' => '2026-11-15',
                'currency' => 'BRL',
            ])
            ->assertRedirect(route('revenues.create'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('revenues', 0);
        $this->assertDatabaseCount('revenue_histories', 0);
    }

    private function createRevenue(User $owner, array $attributes = []): Revenue
    {
        return $owner->revenues()->create(array_merge([
            'name' => 'Receita de teste',
            'amount_minor' => 5000,
            'expected_date' => '2026-11-15',
            'currency' => 'BRL',
            'status' => Revenue::STATUS_PENDING,
        ], $attributes));
    }

    private function createAccount(User $owner): FinancialAccount
    {
        $account = $owner->accounts()->make([
            'name' => 'Conta para recebimentos',
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
