<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\FinancialAccount;
use App\Models\Revenue;
use App\Models\RevenueHistory;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Revenue::class);

        return view('revenues.index', [
            'revenues' => $request->user()->revenues()
                ->orderByDesc('expected_date')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function history(Request $request): View
    {
        $this->authorize('viewAny', Revenue::class);

        return view('revenues.history', [
            'history' => RevenueHistory::query()
                ->whereHas('revenue', fn ($query) => $query->where('user_id', $request->user()->id))
                ->with(['revenue', 'changedBy'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Revenue::class);

        return view('revenues.create', [
            'revenue' => new Revenue(['currency' => 'BRL']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Revenue::class);

        $validated = $request->validate($this->revenueRules());
        $revenue = DB::transaction(function () use ($request, $validated): Revenue {
            $revenue = $request->user()->revenues()->create([
                'name' => $validated['name'],
                'amount_minor' => Money::toMinorUnits($validated['amount']),
                'expected_date' => $validated['expected_date'],
                'source' => $validated['source'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'currency' => $validated['currency'],
            ]);

            $this->recordHistory($revenue, $request, 'created', [
                'after' => $this->snapshot($revenue),
            ]);

            return $revenue;
        });

        return redirect()->route('revenues.show', $revenue)
            ->with('success', 'Receita prevista cadastrada com sucesso.');
    }

    public function show(Request $request, Revenue $revenue): View
    {
        $this->authorize('view', $revenue);
        $request->user()->loadMissing('sharesReceived');

        $accounts = $revenue->status === Revenue::STATUS_PENDING
            ? $request->user()->visibleAccounts()
                ->where('is_active', true)
                ->where('currency', $revenue->currency)
                ->orderBy('name')
                ->get()
                ->filter(fn (FinancialAccount $account): bool => $request->user()->canEditAccount($account))
            : collect();

        return view('revenues.show', [
            'revenue' => $revenue->load(['financialAccount', 'accountTransaction']),
            'history' => $revenue->history()->with('changedBy')->paginate(20),
            'accounts' => $accounts,
        ]);
    }

    public function edit(Revenue $revenue): View
    {
        $this->authorize('update', $revenue);

        return view('revenues.edit', [
            'revenue' => $revenue,
        ]);
    }

    public function update(Request $request, Revenue $revenue): RedirectResponse
    {
        $this->authorize('update', $revenue);
        $rules = $this->revenueRules();

        if ($revenue->received_at !== null) {
            $rules['currency'] = ['required', 'string', 'in:'.$revenue->currency];
        }

        $validated = $request->validate($rules);
        $newAmountMinor = Money::toMinorUnits($validated['amount']);

        DB::transaction(function () use ($request, $revenue, $validated, $newAmountMinor): void {
            $lockedRevenue = $request->user()->revenues()
                ->lockForUpdate()
                ->findOrFail($revenue->id);
            $this->authorize('update', $lockedRevenue);

            $before = $this->snapshot($lockedRevenue);
            $after = [
                'name' => $validated['name'],
                'amount' => Money::format($newAmountMinor),
                'expected_date' => $validated['expected_date'],
                'source' => $validated['source'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'currency' => $validated['currency'],
            ];
            $changes = [];

            foreach ($after as $field => $value) {
                if ($before[$field] !== $value) {
                    $changes[$field] = [
                        'before' => $before[$field],
                        'after' => $value,
                    ];
                }
            }

            if ($changes === []) {
                return;
            }

            if (
                $lockedRevenue->status === Revenue::STATUS_RECEIVED
                && $newAmountMinor !== (int) $lockedRevenue->amount_minor
            ) {
                $account = FinancialAccount::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedRevenue->financial_account_id);
                $original = $account->transactions()
                    ->lockForUpdate()
                    ->findOrFail($lockedRevenue->account_transaction_id);

                $this->reverseReceipt(
                    $original,
                    $request,
                    "Ajuste da receita #{$lockedRevenue->id}",
                );

                $replacement = $account->transactions()->make([
                    'type' => AccountTransaction::TYPE_CREDIT,
                    'amount_minor' => $newAmountMinor,
                    'description' => "Receita recebida: {$validated['name']}",
                ]);
                $replacement->posted_by = $request->user()->id;
                $replacement->save();

                $lockedRevenue->account_transaction_id = $replacement->id;
                $changes['account_transaction_id'] = [
                    'before' => $original->id,
                    'after' => $replacement->id,
                ];
            }

            $lockedRevenue->fill([
                'name' => $validated['name'],
                'amount_minor' => $newAmountMinor,
                'expected_date' => $validated['expected_date'],
                'source' => $validated['source'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'currency' => $validated['currency'],
            ]);
            $lockedRevenue->save();

            $this->recordHistory($lockedRevenue, $request, 'updated', $changes);
        });

        return redirect()->route('revenues.show', $revenue)
            ->with('success', 'Receita atualizada com sucesso.');
    }

    public function destroy(Request $request, Revenue $revenue): RedirectResponse
    {
        $this->authorize('delete', $revenue);

        DB::transaction(function () use ($request, $revenue): void {
            $lockedRevenue = $request->user()->revenues()
                ->lockForUpdate()
                ->findOrFail($revenue->id);
            $this->authorize('delete', $lockedRevenue);

            $this->recordHistory($lockedRevenue, $request, 'deleted', [
                'before' => $this->snapshot($lockedRevenue),
            ]);
            $lockedRevenue->delete();
        });

        return redirect()->route('revenues.index')
            ->with('success', 'Receita excluída; os lançamentos financeiros e o histórico foram preservados.');
    }

    public function receive(Request $request, Revenue $revenue): RedirectResponse
    {
        $this->authorize('update', $revenue);
        $validated = $request->validate([
            'financial_account_id' => ['required', 'integer'],
        ]);

        $request->user()->loadMissing('sharesReceived');

        DB::transaction(function () use ($request, $revenue, $validated): void {
            $lockedRevenue = $request->user()->revenues()
                ->lockForUpdate()
                ->findOrFail($revenue->id);
            $this->authorize('update', $lockedRevenue);

            if ($lockedRevenue->status !== Revenue::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'revenue' => 'Somente receitas pendentes podem ser marcadas como recebidas.',
                ]);
            }

            $account = $request->user()->visibleAccounts()
                ->lockForUpdate()
                ->findOrFail($validated['financial_account_id']);

            abort_unless($request->user()->canEditAccount($account), 403);

            if (! $account->is_active || $account->currency !== $lockedRevenue->currency) {
                throw ValidationException::withMessages([
                    'financial_account_id' => 'Selecione uma conta ativa na mesma moeda da receita.',
                ]);
            }

            $transaction = $account->transactions()->make([
                'type' => AccountTransaction::TYPE_CREDIT,
                'amount_minor' => $lockedRevenue->amount_minor,
                'description' => "Receita recebida: {$lockedRevenue->name}",
            ]);
            $transaction->posted_by = $request->user()->id;
            $transaction->save();

            $lockedRevenue->status = Revenue::STATUS_RECEIVED;
            $lockedRevenue->received_at = now();
            $lockedRevenue->financial_account_id = $account->id;
            $lockedRevenue->account_transaction_id = $transaction->id;
            $lockedRevenue->save();

            $this->recordHistory($lockedRevenue, $request, 'received', [
                'status' => [
                    'before' => Revenue::STATUS_PENDING,
                    'after' => Revenue::STATUS_RECEIVED,
                ],
                'financial_account_id' => $account->id,
                'account_transaction_id' => $transaction->id,
            ]);
        });

        return redirect()->route('revenues.show', $revenue)
            ->with('success', 'Receita marcada como recebida e crédito registrado na conta.');
    }

    public function cancel(Request $request, Revenue $revenue): RedirectResponse
    {
        $this->authorize('update', $revenue);

        DB::transaction(function () use ($request, $revenue): void {
            $lockedRevenue = $request->user()->revenues()
                ->lockForUpdate()
                ->findOrFail($revenue->id);
            $this->authorize('update', $lockedRevenue);

            if (! in_array($lockedRevenue->status, [Revenue::STATUS_PENDING, Revenue::STATUS_RECEIVED], true)) {
                throw ValidationException::withMessages([
                    'revenue' => 'Esta receita já está cancelada.',
                ]);
            }

            $beforeStatus = $lockedRevenue->status;
            $changes = [
                'status' => [
                    'before' => $beforeStatus,
                    'after' => Revenue::STATUS_CANCELLED,
                ],
            ];

            if ($beforeStatus === Revenue::STATUS_RECEIVED) {
                $account = FinancialAccount::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedRevenue->financial_account_id);
                $transaction = $account->transactions()
                    ->lockForUpdate()
                    ->findOrFail($lockedRevenue->account_transaction_id);
                $reversal = $this->reverseReceipt(
                    $transaction,
                    $request,
                    "Cancelamento da receita #{$lockedRevenue->id}",
                );
                $changes['reversal_transaction_id'] = $reversal->id;
            }

            $lockedRevenue->status = Revenue::STATUS_CANCELLED;
            $lockedRevenue->save();

            $this->recordHistory($lockedRevenue, $request, 'cancelled', $changes);
        });

        return redirect()->route('revenues.show', $revenue)
            ->with('success', 'Receita cancelada.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function revenueRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'regex:/^(?=.*[1-9])\d{1,14}(?:\.\d{1,2})?$/D'],
            'expected_date' => ['required', 'date_format:Y-m-d'],
            'source' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function recordHistory(Revenue $revenue, Request $request, string $action, array $changes): void
    {
        $revenue->history()->create([
            'changed_by' => $request->user()->id,
            'action' => $action,
            'changes' => $changes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Revenue $revenue): array
    {
        return [
            'name' => $revenue->name,
            'amount' => $revenue->amount,
            'expected_date' => $revenue->expected_date->toDateString(),
            'source' => $revenue->source,
            'notes' => $revenue->notes,
            'currency' => $revenue->currency,
            'status' => $revenue->status,
            'financial_account_id' => $revenue->financial_account_id,
            'account_transaction_id' => $revenue->account_transaction_id,
            'received_at' => $revenue->received_at?->toIso8601String(),
        ];
    }

    private function reverseReceipt(
        AccountTransaction $transaction,
        Request $request,
        string $description,
    ): AccountTransaction {
        if ($transaction->reversal_of_id !== null || $transaction->reversal()->exists()) {
            throw ValidationException::withMessages([
                'revenue' => 'O lançamento desta receita já foi estornado.',
            ]);
        }

        $reversal = $transaction->account->transactions()->make([
            'type' => $transaction->type === AccountTransaction::TYPE_CREDIT
                ? AccountTransaction::TYPE_DEBIT
                : AccountTransaction::TYPE_CREDIT,
            'amount_minor' => $transaction->amount_minor,
            'description' => $description,
        ]);
        $reversal->posted_by = $request->user()->id;
        $reversal->reversal_of_id = $transaction->id;
        $reversal->save();

        return $reversal;
    }
}
