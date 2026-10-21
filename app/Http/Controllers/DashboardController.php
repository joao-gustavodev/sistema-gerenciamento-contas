<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\FinancialAccount;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', FinancialAccount::class);

        $accounts = $request->user()->accounts();
        $accountBalances = (clone $accounts)
            ->withSum(['transactions as credits_total' => fn ($query) => $query->where('type', 'credit')], 'amount_minor')
            ->withSum(['transactions as debits_total' => fn ($query) => $query->where('type', 'debit')], 'amount_minor')
            ->get();

        return view('dashboard', [
            'counts' => [
                'total' => $accountBalances->count(),
                'active' => $accountBalances->where('is_active', true)->count(),
                'inactive' => $accountBalances->where('is_active', false)->count(),
            ],
            'balancesByCurrency' => $accountBalances->groupBy('currency')->map(
                fn ($currencyAccounts): string => Money::format(
                    $currencyAccounts->sum(fn (FinancialAccount $account): int => (int) $account->current_balance_minor)
                )
            ),
            'recentAccounts' => (clone $accounts)
                ->withSum(['transactions as credits_total' => fn ($query) => $query->where('type', 'credit')], 'amount_minor')
                ->withSum(['transactions as debits_total' => fn ($query) => $query->where('type', 'debit')], 'amount_minor')
                ->latest()
                ->limit(5)
                ->get(),
            'recentTransactions' => AccountTransaction::query()
                ->whereIn('financial_account_id', $request->user()->accounts()->select('id'))
                ->with('account')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
