@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
    <div class="page-heading">
        <h1>Usuários</h1>
        <a class="button" href="{{ route('users.create') }}">Adicionar usuário</a>
    </div>

    @if ($errors->has('user'))
        <div class="error" role="alert">{{ $errors->first('user') }}</div>
    @endif

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Papel</th>
                        <th>Contas</th>
                        <th>Lançamentos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }} @if (auth()->user()->is($user)) (você) @endif</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $roles[$user->role] ?? 'Desconhecido' }}</td>
                            <td>{{ $user->accounts_count }}</td>
                            <td>{{ $user->posted_transactions_count }}</td>
                            <td class="actions">
                                <a href="{{ route('users.edit', $user) }}">Editar</a>
                                @if (! auth()->user()->is($user))
                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                          onsubmit="return confirm('Excluir este usuário? Contas ou lançamentos financeiros vinculados impedem a exclusão.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: #b91c1c">Excluir</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nenhum usuário cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $users->links() }}</div>
    </section>
@endsection
