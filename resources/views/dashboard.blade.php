@extends('layouts.app')

@section('title', 'Visão geral')

@section('content')
    <div class="page-heading">
        <h1>Gestão de contas financeiras</h1>
        @can('create', \App\Models\FinancialAccount::class)
            <a class="button" href="{{ route('accounts.create') }}">Adicionar conta</a>
        @endcan
    </div>

    <section class="stats" aria-label="Resumo das contas">
        <a class="stat" href="{{ route('accounts.index') }}">Contas<strong>{{ $counts['total'] }}</strong></a>
        <a class="stat" href="{{ route('accounts.index', ['active' => '1']) }}">Ativas<strong>{{ $counts['active'] }}</strong></a>
        <a class="stat" href="{{ route('accounts.index', ['active' => '0']) }}">Arquivadas<strong>{{ $counts['inactive'] }}</strong></a>
        @foreach ($balancesByCurrency as $currency => $balance)
            <div class="stat">Saldo total ({{ $currency }})<strong>{{ $currency }} {{ $balance }}</strong></div>
        @endforeach
    </section>

    <section class="card">
        <div class="page-heading">
            <h2>Contas recentes</h2>
            <a href="{{ route('accounts.index') }}">Ver todas</a>
        </div>
        @if ($recentAccounts->isEmpty())
            <p>Nenhuma conta financeira cadastrada.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Conta</th>
                            <th>Instituição</th>
                            <th>Número</th>
                            <th>Saldo atual</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentAccounts as $account)
                            <tr>
                                <td><a href="{{ route('accounts.show', $account) }}">{{ $account->name }}</a></td>
                                <td>{{ $account->bank_name }}</td>
                                <td>{{ $account->account_number }}</td>
                                <td>{{ $account->currency }} {{ $account->current_balance }}</td>
                                <td>{{ $account->is_active ? 'Ativa' : 'Arquivada' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($recentTransactions->isNotEmpty())
        <section class="card" style="margin-top: 1rem">
            <h2>Lançamentos recentes</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Data</th><th>Conta</th><th>Tipo</th><th>Descrição</th><th>Valor</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td><a href="{{ route('accounts.show', $transaction->account) }}">{{ $transaction->account->name }}</a></td>
                                <td>{{ $transaction->type === 'credit' ? 'Crédito' : 'Débito' }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td>{{ $transaction->account->currency }} {{ $transaction->amount }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
