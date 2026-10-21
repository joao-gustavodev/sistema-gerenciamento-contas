@extends('layouts.app')

@section('title', 'Receitas')

@section('content')
    <div class="page-heading">
        <h1>Receitas previstas</h1>
        <div class="actions">
            <a class="button secondary" href="{{ route('revenues.history') }}">Histórico</a>
            @can('create', \App\Models\Revenue::class)
                <a class="button" href="{{ route('revenues.create') }}">Adicionar receita</a>
            @endcan
        </div>
    </div>

    <section class="card">
        @if ($revenues->isEmpty())
            <p>Nenhuma receita cadastrada.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Receita</th>
                            <th>Previsão</th>
                            <th>Fonte de renda</th>
                            <th>Valor</th>
                            <th>Situação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($revenues as $revenue)
                            <tr>
                                <td><a href="{{ route('revenues.show', $revenue) }}">{{ $revenue->name }}</a></td>
                                <td>{{ $revenue->expected_date->format('d/m/Y') }}</td>
                                <td>{{ $revenue->source ?: '—' }}</td>
                                <td>{{ $revenue->currency }} {{ $revenue->amount }}</td>
                                <td>{{ \App\Models\Revenue::STATUSES[$revenue->status] }}</td>
                                <td><a href="{{ route('revenues.show', $revenue) }}">Detalhes</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $revenues->links() }}</div>
        @endif
    </section>
@endsection
