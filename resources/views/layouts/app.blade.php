<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestão de Contas')</title>
    <style>
        :root { font-family: system-ui, sans-serif; color: #1f2937; background: #f3f4f6; }
        body { margin: 0; }
        header { background: #172554; color: white; padding: 1rem max(1rem, calc((100% - 1100px) / 2)); }
        header a { color: white; text-decoration: none; font-weight: 700; }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .page-heading, .actions, .inline-form { display: flex; align-items: center; gap: .75rem; }
        .page-heading { justify-content: space-between; margin-bottom: 1.25rem; }
        h1 { margin: 0; font-size: 1.6rem; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: .6rem; padding: 1.25rem; }
        .main-nav, .tabs { display: flex; gap: .5rem; flex-wrap: wrap; }
        .main-nav { margin-top: .85rem; }
        .main-nav a, .tabs a { display: inline-block; padding: .5rem .8rem; border-radius: .35rem; text-decoration: none; }
        .main-nav a { color: #dbeafe; }
        .main-nav a.active { background: #1e3a8a; color: white; }
        .tabs { margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
        .tabs a { color: #4b5563; margin-bottom: -1px; border-bottom: 2px solid transparent; }
        .tabs a.active { color: #1d4ed8; border-bottom-color: #1d4ed8; font-weight: 700; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .stat { background: white; border: 1px solid #e5e7eb; border-radius: .6rem; padding: 1rem; }
        .stat strong { display: block; font-size: 1.6rem; margin-top: .35rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .8rem .65rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { color: #4b5563; font-size: .85rem; }
        .description { display: block; color: #6b7280; margin-top: .25rem; max-width: 38rem; }
        .button, button { display: inline-block; border: 0; border-radius: .35rem; padding: .6rem .9rem; background: #1d4ed8; color: white; text-decoration: none; font: inherit; cursor: pointer; }
        .button.secondary { background: #e5e7eb; color: #1f2937; }
        input, select, textarea { box-sizing: border-box; width: 100%; border: 1px solid #cbd5e1; border-radius: .35rem; padding: .65rem; font: inherit; }
        label { display: block; font-weight: 600; margin: 1rem 0 .35rem; }
        .alert { padding: .8rem 1rem; margin-bottom: 1rem; border-radius: .35rem; background: #dcfce7; color: #166534; }
        .error { color: #b91c1c; margin: .3rem 0 0; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .pagination { display: flex; justify-content: space-between; margin-top: 1rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 1rem; }
        .form-grid .full { grid-column: 1 / -1; }
        .search-form { display: flex; gap: .5rem; max-width: 34rem; margin-bottom: 1rem; }
        .search-form input { flex: 1; }
        .inline-field { width: auto; }
        @media (max-width: 720px) { .table-wrap { overflow-x: auto; } table { min-width: 700px; } }
        @media (max-width: 560px) { .form-grid { grid-template-columns: 1fr; } .form-grid .full { grid-column: auto; } }
    </style>
</head>
<body>
    <header>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">SGC · Gestão de Contas</a>
        <nav class="main-nav" aria-label="Navegação principal">
            @auth
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Visão geral</a>
                <a href="{{ route('accounts.index') }}" @class(['active' => request()->routeIs('accounts.*')])>Contas financeiras</a>
                <a href="{{ route('revenues.index') }}" @class(['active' => request()->routeIs('revenues.*')])>Receitas</a>
                <a href="{{ route('sharing.index') }}" @class(['active' => request()->routeIs('sharing.*')])>Compartilhamento</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('users.index') }}" @class(['active' => request()->routeIs('users.*')])>Usuários</a>
                    <a href="{{ route('permissions.index') }}" @class(['active' => request()->routeIs('permissions.*')])>Permissões</a>
                @endif
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="button secondary">Sair</button>
                </form>
            @else
                <a href="{{ route('login') }}" @class(['active' => request()->routeIs('login')])>Entrar</a>
            @endauth
        </nav>
    </header>
    <main>
        @if (session('success'))
            <div class="alert" role="status">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
