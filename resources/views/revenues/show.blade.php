@extends('layouts.app')

@section('title', $revenue->name)

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
            <h1>{{ $revenue->name }}</h1>
            <p>{{ $revenue->currency }} {{ $revenue->amount }} · previsão para {{ $revenue->expected_date->format('d/m/Y') }}</p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('revenues.index') }}">Voltar</a>
            @can('update', $revenue)
                <a class="button" href="{{ route('revenues.edit', $revenue) }}">Editar</a>
            @endcan
        </div>
    </div>

    <section class="stats">
        <div class="stat">Situação<strong>{{ \App\Models\Revenue::STATUSES[$revenue->status] }}</strong></div>
        <div class="stat">Fonte de renda<strong>{{ $revenue->source ?: '—' }}</strong></div>
        @if ($revenue->received_at)
            <div class="stat">Recebida em<strong>{{ $revenue->received_at->format('d/m/Y H:i') }}</strong></div>
            <div class="stat">Conta financeira<strong>{{ $revenue->financialAccount?->name ?? 'Conta indisponível' }}</strong></div>
        @endif
    </section>

    <section class="card" style="margin-bottom: 1rem">
        <h2>Observações</h2>
        <p>{{ $revenue->notes ?: 'Nenhuma observação.' }}</p>
        @can('update', $revenue)
            @if ($revenue->status === \App\Models\Revenue::STATUS_PENDING)
                <h2>Registrar recebimento</h2>
                @if ($accounts->isEmpty())
                    <p>Não há contas financeiras ativas e editáveis na moeda {{ $revenue->currency }}.</p>
                @else
                    <form action="{{ route('revenues.receive', $revenue) }}" method="POST" class="inline-form">
                        @csrf
                        <label for="financial_account_id">Conta financeira</label>
                        <select class="inline-field" id="financial_account_id" name="financial_account_id" required>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Marcar como recebida</button>
                    </form>
                @endif
            @endif
            @if (in_array($revenue->status, [\App\Models\Revenue::STATUS_PENDING, \App\Models\Revenue::STATUS_RECEIVED], true))
                <form action="{{ route('revenues.cancel', $revenue) }}" method="POST" style="margin-top: 1rem">
                    @csrf
                    <button type="submit" class="button secondary">Cancelar receita</button>
                </form>
            @endif
            <form action="{{ route('revenues.destroy', $revenue) }}" method="POST" style="margin-top: 1rem" onsubmit="return confirm('Excluir esta receita? O histórico e os lançamentos financeiros serão preservados.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="button secondary">Excluir receita</button>
            </form>
        @endcan
    </section>

    <section class="card">
        <div class="page-heading">
            <h2>Histórico de alterações</h2>
            <a href="{{ route('revenues.history') }}">Ver histórico completo</a>
        </div>
        @if ($history->isEmpty())
            <p>Nenhuma alteração registrada.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Data</th><th>Ação</th><th>Usuário</th><th>Detalhes</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $entry)
                            <tr>
                                <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ ['created' => 'Criada', 'updated' => 'Editada', 'received' => 'Recebida', 'cancelled' => 'Cancelada', 'deleted' => 'Excluída'][$entry->action] ?? $entry->action }}</td>
                                <td>{{ $entry->changedBy?->name ?? 'Usuário removido' }}</td>
                                <td>
                                    <details>
                                        <summary>Ver alterações</summary>
                                        <pre>{{ json_encode($entry->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $history->links() }}</div>
        @endif
    </section>
@endsection
