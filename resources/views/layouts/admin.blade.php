<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('page-title', '管理画面') - {{ config('app.name', 'KomaFull') }}</title>

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/app.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/pages/admin.css') }}">
        @stack('styles')
    </head>
    <body>
        <div class="p-admin-layout d-flex vh-100">
            <nav id="admin-sidebar" class="p-admin-layout__sidebar d-flex flex-column flex-shrink-0 bg-dark text-white p-3">
                <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center mb-3 text-white text-decoration-none">
                    <span class="fs-5 fw-semibold">{{ config('app.name', 'KomaFull') }}</span>
                </a>
                <hr>
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.dashboard')) active @endif">
                            ダッシュボード
                        </a>
                    </li>
                </ul>
                <hr>
                <div class="dropdown">
                    <span class="text-white-50 small">{{ Auth::user()?->name ?? '' }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm w-100">ログアウト</button>
                    </form>
                </div>
            </nav>

            <div id="admin-main" class="p-admin-layout__main flex-grow-1 d-flex flex-column overflow-auto">
                <header class="p-admin-layout__header bg-white border-bottom px-4 py-3 d-flex align-items-center">
                    <h1 class="h5 mb-0">@yield('page-title', 'ダッシュボード')</h1>
                </header>
                <main class="p-4 flex-grow-1">
                    @yield('content')
                </main>
            </div>
        </div>

        <script defer src="{{ v_asset('assets/vendor/alpine/alpine.csp.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/js/app.js') }}"></script>
        @stack('scripts')
    </body>
</html>
