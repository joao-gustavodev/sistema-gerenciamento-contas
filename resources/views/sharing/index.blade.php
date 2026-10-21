@extends('layouts.app')

@section('title', 'Compartilhamento')

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
        <h1>Compartilhamento de dados</h1>
    </div>

    <section class="card" style="margin-bottom: 1rem">
        <h2>Conceder acesso</h2>
        <p>O acesso inclui suas contas bancárias e respectivos lançamentos de receitas e despesas. Relatórios não são compartilhados. O compartilhamento de agendas ainda não está disponível neste sistema.</p>
        @if ($recipients->isEmpty())
            <p>Não há usuários disponíveis para compartilhar no momento.</p>
        @else
            <form action="{{ route('sharing.store') }}" method="POST">
                @csrf
                <div class="form-grid">
                    <div>
                        <label for="recipient_id">Usuário</label>
                        <select id="recipient_id" name="recipient_id" required>
                            <option value="">Selecione um usuário</option>
                            @foreach ($recipients as $recipient)
                                <option value="{{ $recipient->id }}" @selected(old('recipient_id') == $recipient->id)>
                                    {{ $recipient->name }} ({{ $recipient->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('recipient_id') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="permission">Permissão</label>
                        <select id="permission" name="permission" required>
                            @foreach ($permissions as $value => $label)
                                <option value="{{ $value }}" @selected(old('permission', 'view') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('permission') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit">Compartilhar</button>
            </form>
        @endif
    </section>

    <section class="card">
        <h2>Acessos concedidos por você</h2>
        @if ($shares->isEmpty())
            <p>Você ainda não compartilhou seus dados com ninguém.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Permissão</th>
                            <th>Concedido por</th>
                            <th>Concedido em</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shares as $share)
                            <tr>
                                <td>{{ $share->recipient->name }}<span class="description">{{ $share->recipient->email }}</span></td>
                                <td>
                                    @if ($share->status === \App\Models\UserShare::STATUS_ACTIVE)
                                        <form action="{{ route('sharing.update', $share) }}" method="POST" class="inline-form">
                                            @csrf
                                            @method('PUT')
                                            <label class="sr-only" for="permission-{{ $share->id }}">Permissão de {{ $share->recipient->name }}</label>
                                            <select class="inline-field" id="permission-{{ $share->id }}" name="permission">
                                                @foreach ($permissions as $value => $label)
                                                    <option value="{{ $value }}" @selected($share->permission === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit">Salvar</button>
                                        </form>
                                    @else
                                        {{ $permissions[$share->permission] }}
                                    @endif
                                </td>
                                <td>{{ $share->grantor->name }}</td>
                                <td>{{ $share->granted_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $share->status === \App\Models\UserShare::STATUS_ACTIVE ? 'Ativo' : 'Revogado' }}</td>
                                <td>
                                    @if ($share->status === \App\Models\UserShare::STATUS_ACTIVE)
                                        <form action="{{ route('sharing.destroy', $share) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button secondary">Revogar</button>
                                        </form>
                                    @elseif ($share->revoked_at)
                                        Revogado em {{ $share->revoked_at->format('d/m/Y H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $shares->links() }}</div>
        @endif
    </section>
@endsection
