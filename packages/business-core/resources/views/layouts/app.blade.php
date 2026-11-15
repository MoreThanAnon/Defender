<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bc-ink: #173b33; --bc-accent: #b7791f; --bc-soft: #e8f1ee; }
        body { background: #f6f5f1; color: #1f2d29; }
        .bc-topbar { background: var(--bc-ink); }
        .bc-topbar a, .bc-topbar .navbar-brand, .bc-topbar .btn-link { color: #fff; text-decoration: none; }
        .bc-subnav { background: #fff; border-bottom: 1px solid #e3e1d9; }
        .bc-subnav .nav-link { color: #44524e; padding: .7rem .8rem; border-bottom: 3px solid transparent; border-radius: 0; white-space: nowrap; }
        .bc-subnav .nav-link.active { color: var(--bc-ink); border-bottom-color: var(--bc-ink); font-weight: 600; }
        .bc-subnav .nav { flex-wrap: nowrap; overflow-x: auto; }
        .card { border-color: #e3e1d9; }
        .btn-primary { background: var(--bc-ink); border-color: var(--bc-ink); }
        .btn-primary:hover, .btn-primary:focus { background: #0f2a24; border-color: #0f2a24; }
        .btn-outline-primary { color: var(--bc-ink); border-color: var(--bc-ink); }
        .btn-outline-primary:hover { background: var(--bc-ink); border-color: var(--bc-ink); }
        a { color: #1c5a4c; }
        .bc-figure { font-size: 1.6rem; font-weight: 700; color: var(--bc-ink); font-variant-numeric: tabular-nums; }
        .bc-source { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7672; }
        .bc-storage { background: #fff4de; border-bottom: 1px solid #f0d9a8; color: #6b4a0b; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        @media print { .bc-topbar, .bc-subnav, .bc-storage, .no-print { display: none !important; } body { background: #fff; } }
    </style>
    @stack('head')
</head>
<body>
    <nav class="navbar navbar-expand bc-topbar py-2">
        <div class="container-xl">
            <a class="navbar-brand fw-bold" href="{{ url('/') }}">{{ config('app.name') }}</a>
            <div class="ms-auto d-flex align-items-center gap-3 small">
                @auth
                    <a href="{{ route('bc.index') }}">My businesses</a>
                    @if (\BusinessCore\Support\CurrentBusiness::isPlatformAdmin(auth()->user()))
                        <a href="{{ route('bc.admin.index') }}">Storage &amp; admin</a>
                    @endif
                    @if (Route::has('account.edit'))
                        <a href="{{ route('account.edit') }}">{{ \BusinessCore\Support\CurrentBusiness::displayName(auth()->user()) }}</a>
                    @endif
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf
                            <button class="btn btn-link btn-sm p-0">Sign out</button>
                        </form>
                    @endif
                @else
                    @if (Route::has('login'))<a href="{{ route('login') }}">Sign in</a>@endif
                    @if (Route::has('register'))<a href="{{ route('register') }}">Register</a>@endif
                @endauth
            </div>
        </div>
    </nav>

    @include('business-core::partials.business-nav')

    <main class="container-xl py-4">
        @include('business-core::partials.flash')
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
