@extends('layouts.app')

@section('title', $account->name)

@section('content')
    @if ($errors->any())
        <div class="error" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="page-heading">
        <div>
            <h1>{{ $account->name }}</h1>
            <p>{{ $account->bank_name }} · Agência {{ $account->branch ?: '—' }} · Conta {{ $account->account_number }}</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('accounts.index') }}">Voltar</a>
            @can('update', $account)
                <a class="button" href="{{ route('accounts.edit', $account) }}">Editar conta</a>
            @endcan
        </div>
    </div>

    <section class="stats">
        <div class="stat">Saldo inicial<strong>{{ $account->currency }} {{ $account->opening_balance }}</strong></div>
        <div class="stat">Saldo atual<strong>{{ $account->currency }} {{ $account->current_balance }}</strong></div>
        <div class="stat">Situação<strong>{{ $account->is_active ? 'Ativa' : 'Arquivada' }}</strong></div>
    </section>

    @cannot('update', $account)
        <p role="status">Acesso somente para visualização.</p>
    @endcannot

    @can('createTransaction', $account)
        @if ($account->is_active)
            <section class="card" style="margin-bottom: 1rem">
                <h2>Registrar lançamento</h2>
                <form action="{{ route('accounts.transactions.store', $account) }}" method="POST">
                    @csrf
                    <div class="form-grid">
                        <div>
                            <label for="type">Movimentação</label>
                            <select id="type" name="type" required>
                                <option value="credit">Crédito</option>
                                <option value="debit">Débito</option>
                            </select>
                            @error('type') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="amount">Valor ({{ $account->currency }})</label>
                            <input id="amount" name="amount" type="number" min="0.01" step="0.01" required value="{{ old('amount') }}">
                            @error('amount') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="full">
                            <label for="description">Descrição</label>
                            <input id="description" name="description" maxlength="255" required value="{{ old('description') }}">
                            @error('description') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button type="submit">Registrar</button>
                </form>
            </section>
        @endif
    @endcan

    <section class="card">
        <h2>Extrato</h2>
        @if ($transactions->isEmpty())
            <p>Nenhum lançamento. O saldo atual corresponde ao saldo inicial.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Registrado por</th>
                            @can('reverseTransaction', $account)
                                <th>Ações</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $transaction->type === 'credit' ? 'Crédito' : 'Débito' }}
                                    @if ($transaction->reversal_of_id)
                                        · Estorno de #{{ $transaction->reversal_of_id }}
                                    @endif
                                </td>
                                <td>{{ $transaction->description }}</td>
                                <td>{{ $account->currency }} {{ $transaction->amount }}</td>
                                <td>{{ $transaction->poster?->name ?? 'Conta encerrada' }}</td>
                                @can('reverseTransaction', $account)
                                    <td>
                                        @if (! $transaction->reversal_of_id && ! $transaction->reversal && ! $transaction->revenueReceipt)
                                            <form action="{{ route('accounts.transactions.reverse', [$account, $transaction]) }}" method="POST">
                                                @csrf
                                                <label class="sr-only" for="reason-{{ $transaction->id }}">Motivo do estorno do lançamento #{{ $transaction->id }}</label>
                                                <input id="reason-{{ $transaction->id }}" name="reason" maxlength="200" placeholder="Motivo do estorno" required>
                                                <button type="submit">Estornar</button>
                                            </form>
                                        @else
                                            <span>Sem ação</span>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $transactions->links() }}</div>
        @endif
    </section>
@endsection
