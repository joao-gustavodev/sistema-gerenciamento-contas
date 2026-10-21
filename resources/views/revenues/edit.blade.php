@extends('layouts.app')

@section('title', 'Editar receita')

@section('content')
    <div class="page-heading">
        <h1>Editar receita</h1>
        <a class="button secondary" href="{{ route('revenues.show', $revenue) }}">Voltar</a>
    </div>
    <section class="card">
        <form action="{{ route('revenues.update', $revenue) }}" method="POST">
            @csrf
            @method('PUT')
            @include('revenues.form', ['revenue' => $revenue])
            <button type="submit">Salvar alterações</button>
        </form>
    </section>
@endsection
