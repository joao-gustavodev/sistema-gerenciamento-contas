@extends('layouts.app')

@section('title', 'Histórico de receitas')

@section('content')
    <div class="page-heading">
        <h1>Histórico de receitas</h1>
        <a class="button secondary" href="{{ route('revenues.index') }}">Voltar às receitas</a>
    </div>
    <section class="card">
        @if ($history->isEmpty())
            <p>Nenhuma alteração registrada.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Data</th><th>Receita</th><th>Ação</th><th>Usuário</th><th>Detalhes</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $entry)
                            <tr>
                                <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $entry->revenue?->name ?? 'Receita excluída' }}
                                    @if ($entry->revenue?->trashed())
                                        (excluída)
                                    @endif
                                </td>
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
