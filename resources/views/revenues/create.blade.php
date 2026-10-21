@extends('layouts.app')

@section('title', 'Nova receita prevista')

@section('content')
    <div class="page-heading">
        <h1>Nova receita prevista</h1>
        <a class="button secondary" href="{{ route('revenues.index') }}">Voltar</a>
    </div>
    <section class="card">
        <p>Esta receita é uma previsão. O sistema não criará previsões futuras automaticamente.</p>
        <form action="{{ route('revenues.store') }}" method="POST">
            @csrf
            @include('revenues.form', ['revenue' => $revenue])
            <button type="submit">Cadastrar previsão</button>
        </form>
    </section>
@endsection
