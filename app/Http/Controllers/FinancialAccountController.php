<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancialAccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', FinancialAccount::class);
        $request->user()->loadMissing('sharesReceived');

        $search = trim((string) $request->query('search', ''));
        $active = $request->query('active');

        $accounts = $request->user()->visibleAccounts()
            ->withSum(['transactions as credits_total' => fn ($query) => $query->where('type', 'credit')], 'amount_minor')
            ->withSum(['transactions as debits_total' => fn ($query) => $query->where('type', 'debit')], 'amount_minor')
            ->latest();

        if ($search !== '') {
            $accounts->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        if ($active === '1' || $active === '0') {
            $accounts->where('is_active', $active === '1');
        } else {
            $active = '';
        }

        return view('accounts.index', [
            'accounts' => $accounts->paginate(15)->withQueryString(),
            'search' => $search,
            'active' => $active,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FinancialAccount::class);

        return view('accounts.create', [
            'types' => FinancialAccount::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', FinancialAccount::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bank_name' => ['required', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:40'],
            'account_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('financial_accounts', 'account_number')
                    ->where(fn ($query) => $query->where('bank_name', $request->input('bank_name'))
                        ->where('branch', $request->input('branch'))),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(FinancialAccount::TYPES))],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'opening_balance' => ['required', 'regex:/^-?\d{1,14}(?:\.\d{1,2})?$/D'],
        ]);

        $openingBalanceMinor = Money::toMinorUnits($validated['opening_balance']);
        unset($validated['opening_balance']);

        $account = $request->user()->accounts()->make($validated);
        $account->opening_balance_minor = $openingBalanceMinor;
        $account->save();

        return redirect()->route('accounts.index')
            ->with('success', 'Conta financeira criada com sucesso.');
    }

    public function show(FinancialAccount $account): View
    {
        $this->authorize('view', $account);

        $account->loadSum([
            'transactions as credits_total' => fn ($query) => $query->where('type', 'credit'),
            'transactions as debits_total' => fn ($query) => $query->where('type', 'debit'),
        ], 'amount_minor');

        return view('accounts.show', [
            'account' => $account,
            'transactions' => $account->transactions()
                ->with(['poster', 'reversal', 'revenueReceipt'])
                ->latest('created_at')
                ->paginate(20),
        ]);
    }

    public function edit(FinancialAccount $account): View
    {
        $this->authorize('update', $account);

        return view('accounts.edit', [
            'account' => $account,
            'types' => FinancialAccount::TYPES,
        ]);
    }

    public function update(Request $request, FinancialAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bank_name' => ['required', 'string', 'max:120'],
            'branch' => ['nullable', 'string', 'max:40'],
            'account_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('financial_accounts', 'account_number')
                    ->where(fn ($query) => $query->where('bank_name', $request->input('bank_name'))
                        ->where('branch', $request->input('branch')))
                    ->ignore($account->id),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(FinancialAccount::TYPES))],
            'is_active' => ['required', 'boolean'],
        ]);

        $account->update($validated);

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Conta financeira atualizada com sucesso.');
    }
}
