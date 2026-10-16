@extends('layouts.app')

@section('title', 'Adicionar usuário')

@section('content')
    <div class="page-heading">
        <h1>Adicionar usuário</h1>
        <a class="button secondary" href="{{ route('users.index') }}">Voltar</a>
    </div>

    <section class="card">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            @include('users.form', ['user' => null])
            <button type="submit">Criar usuário</button>
        </form>
    </section>
@endsection
