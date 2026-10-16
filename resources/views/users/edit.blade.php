@extends('layouts.app')

@section('title', 'Editar usuário')

@section('content')
    <div class="page-heading">
        <h1>Editar usuário</h1>
        <a class="button secondary" href="{{ route('users.index') }}">Voltar</a>
    </div>

    <section class="card">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            @include('users.form', ['user' => $user])
            <button type="submit">Salvar alterações</button>
        </form>
    </section>
@endsection
