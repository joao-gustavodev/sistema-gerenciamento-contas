@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
    <div class="page-heading">
        <h1>Entrar no SGC · Gestão de Contas</h1>
    </div>

    <section class="card">
        <form action="{{ route('login.store') }}" method="POST">
            @csrf

            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
            @error('email') <p class="error" role="alert">{{ $message }}</p> @enderror

            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            @error('password') <p class="error" role="alert">{{ $message }}</p> @enderror

            <label>
                <input name="remember" type="checkbox" value="1" style="width: auto">
                Manter conectado
            </label>

            <div class="actions" style="margin-top: 1rem">
                <button type="submit">Entrar</button>
            </div>
        </form>
    </section>
@endsection
