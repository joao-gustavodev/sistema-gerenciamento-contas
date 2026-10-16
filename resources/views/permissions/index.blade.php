@extends('layouts.app')

@section('title', 'Permissões')

@section('content')
    <div class="page-heading">
        <h1>Papéis e permissões</h1>
    </div>

    <section class="card">
        <p>As permissões são aplicadas no servidor; esconder abas não concede nem substitui acesso.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Papel</th>
                        <th>Contas financeiras</th>
                        <th>Usuários e permissões</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>{{ $roles[\App\Models\User::ROLE_ADMIN] }}</th>
                        <td>Visualizar, cadastrar, editar, arquivar contas e registrar ou estornar lançamentos.</td>
                        <td>Gerenciar usuários e atribuir papéis.</td>
                    </tr>
                    <tr>
                        <th>{{ $roles[\App\Models\User::ROLE_EDITOR] }}</th>
                        <td>Visualizar, cadastrar, editar e arquivar contas; registrar e estornar lançamentos.</td>
                        <td>Sem acesso à gestão de contas ou papéis.</td>
                    </tr>
                    <tr>
                        <th>{{ $roles[\App\Models\User::ROLE_VIEWER] }}</th>
                        <td>Visualizar todas as contas e seus saldos e lançamentos; sem alterações.</td>
                        <td>Sem acesso à gestão de contas ou papéis.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
