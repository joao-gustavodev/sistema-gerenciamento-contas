@extends('layouts.app')

@section('title', 'Adicionar conta')

@section('content')
    <div class="page-heading">
        <h1>Adicionar conta financeira</h1>
        <a class="button secondary" href="{{ route('accounts.index') }}">Voltar</a>
    </div>
    <section class="card">
        <form action="{{ route('accounts.store') }}" method="POST">
            @csrf
            @include('accounts.form', ['account' => null])
            <button type="submit">Criar conta</button>
        </form>
    </section>
@endsection
