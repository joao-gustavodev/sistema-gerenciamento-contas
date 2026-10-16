@extends('layouts.app')

@section('title', 'Editar conta')

@section('content')
    <div class="page-heading">
        <h1>Editar conta</h1>
        <a class="button secondary" href="{{ route('accounts.show', $account) }}">Cancelar</a>
    </div>
    <section class="card">
        <form action="{{ route('accounts.update', $account) }}" method="POST">
            @csrf
            @method('PUT')
            @include('accounts.form', ['account' => $account])
            <button type="submit">Salvar alterações</button>
        </form>
    </section>
@endsection
