@extends('layouts.app')

@section('title', 'Contas financeiras')

@section('content')
    <div class="page-heading">
        <h1>Contas financeiras</h1>
        @can('create', \App\Models\FinancialAccount::class)
            <a class="button" href="{{ route('accounts.create') }}">Adicionar conta</a>
        @endcan
    </div>

    <section class="card">
        <form class="search-form" action="{{ route('accounts.index') }}" method="GET">
            <label class="sr-only" for="account-search">Buscar contas</label>
            <input id="account-search" name="search" value="{{ $search }}" placeholder="Buscar por conta, banco ou número">
            <select name="active" aria-label="Filtrar por situação">
                <option value="">Todas</option>
                <option value="1" @selected($active === '1')>Ativas</option>
                <option value="0" @selected($active === '0')>Arquivadas</option>
            </select>
            <button type="submit">Filtrar</button>
        </form>

        @if ($accounts->isEmpty())
            <p>Nenhuma conta financeira encontrada.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Conta</th>
                            <th>Banco</th>
                            <th>Agência</th>
                            <th>Número</th>
                            <th>Tipo</th>
                            <th>Saldo atual</th>
                            <th>Situação</th>
                            @if (auth()->user()->canEditAccounts())
                                <th>Ações</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            <tr>
                                <td><a href="{{ route('accounts.show', $account) }}">{{ $account->name }}</a></td>
                                <td>{{ $account->bank_name }}</td>
                                <td>{{ $account->branch ?: '—' }}</td>
                                <td>{{ $account->account_number }}</td>
                                <td>{{ \App\Models\FinancialAccount::TYPES[$account->type] }}</td>
                                <td>{{ $account->currency }} {{ $account->current_balance }}</td>
                                <td>{{ $account->is_active ? 'Ativa' : 'Arquivada' }}</td>
                                @can('update', $account)
                                    <td><a href="{{ route('accounts.edit', $account) }}">Editar</a></td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $accounts->links() }}</div>
        @endif
    </section>
@endsection
