<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\FinancialAccount;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountTransactionController extends Controller
{
    public function store(Request $request, FinancialAccount $account): RedirectResponse
    {
        $this->authorize('createTransaction', $account);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:credit,debit'],
            'amount' => ['required', 'regex:/^(?=.*[1-9])\d{1,14}(?:\.\d{1,2})?$/D'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $account, $validated): void {
            $lockedAccount = FinancialAccount::query()->lockForUpdate()->findOrFail($account->id);

            if (! $lockedAccount->is_active) {
                throw ValidationException::withMessages([
                    'account' => 'Não é possível lançar movimentações em uma conta inativa.',
                ]);
            }

            $transaction = $lockedAccount->transactions()->make([
                'type' => $validated['type'],
                'amount_minor' => Money::toMinorUnits($validated['amount']),
                'description' => $validated['description'],
            ]);
            $transaction->posted_by = $request->user()->id;
            $transaction->save();
        });

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Lançamento registrado com sucesso.');
    }

    public function reverse(Request $request, FinancialAccount $account, AccountTransaction $transaction): RedirectResponse
    {
        $this->authorize('reverseTransaction', $account);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        DB::transaction(function () use ($request, $account, $transaction, $validated): void {
            $lockedAccount = FinancialAccount::query()->lockForUpdate()->findOrFail($account->id);
            $original = $lockedAccount->transactions()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($original->reversal_of_id !== null) {
                throw ValidationException::withMessages([
                    'transaction' => 'Um estorno não pode ser estornado diretamente.',
                ]);
            }

            if ($lockedAccount->transactions()->where('reversal_of_id', $original->id)->exists()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Este lançamento já possui um estorno.',
                ]);
            }

            $reversal = $lockedAccount->transactions()->make([
                'type' => $original->type === AccountTransaction::TYPE_CREDIT
                    ? AccountTransaction::TYPE_DEBIT
                    : AccountTransaction::TYPE_CREDIT,
                'amount_minor' => $original->amount_minor,
                'description' => "Estorno do lançamento #{$original->id}: {$validated['reason']}",
            ]);
            $reversal->posted_by = $request->user()->id;
            $reversal->reversal_of_id = $original->id;
            $reversal->save();
        });

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Estorno registrado; o lançamento original foi preservado.');
    }
}
