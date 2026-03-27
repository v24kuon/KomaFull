<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'マイページ') — {{ config('app.name', 'KomaFull') }}</title>

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/fonts/vendor-fonts.css') }}">

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/app.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/pages/member.css') }}">
        @stack('styles')
    </head>
    <body class="d-flex flex-column min-vh-100 p-member-layout" hx-headers='{"X-CSRF-TOKEN":"{{ csrf_token() }}"}'>
        <header class="p-member-layout__header border-bottom bg-body shadow-sm">
            <nav class="navbar navbar-expand-lg container py-2">
                <a class="navbar-brand fw-semibold" href="{{ route('member.dashboard') }}">{{ config('app.name', 'KomaFull') }}</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#memberNav" aria-controls="memberNav" aria-expanded="false" aria-label="ナビを開く">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="memberNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link @if(request()->routeIs('member.dashboard')) active @endif" href="{{ route('member.dashboard') }}">マイページ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if(request()->routeIs('member.profile.*')) active @endif" href="{{ route('member.profile.edit') }}">プロフィール</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if(request()->routeIs('member.settings.*')) active @endif" href="{{ route('member.settings.index') }}">会員設定</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}">ホーム</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('schedule.index') }}">開催枠</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('programs.index') }}">プログラム</a>
                        </li>
                        @can('access-admin')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.dashboard') }}">管理画面</a>
                            </li>
                        @endcan
                    </ul>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-secondary d-none d-md-inline">{{ Auth::user()?->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">ログアウト</button>
                        </form>
                    </div>
                </div>
            </nav>
        </header>

        <main class="p-member-layout__main flex-grow-1 py-4">
            <div class="container">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>

        @include('partials.public.site-footer')

        <script defer src="{{ v_asset('assets/js/app.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/alpine/alpine.csp.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/htmx/htmx.min.js') }}"></script>
        @stack('scripts')
    </body>
</html>
