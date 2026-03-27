<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('page-title', '管理画面') - {{ config('app.name', 'KomaFull') }}</title>

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/fonts/vendor-fonts.css') }}">

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/app.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/pages/admin.css') }}">
        @stack('styles')
    </head>
    <body hx-headers='{"X-CSRF-TOKEN":"{{ csrf_token() }}"}'>
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
                    <li class="nav-item mt-3">
                        <span class="text-white-50 small text-uppercase px-3">マスタ管理</span>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.categories.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.categories.*')) active @endif">
                            カテゴリ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.program-types.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.program-types.*')) active @endif">
                            プログラム種別
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.programs.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.programs.*')) active @endif">
                            プログラム
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.program-repetition-rules.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.program-repetition-rules.*')) active @endif">
                            繰り返し設定
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.locations.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.locations.*')) active @endif">
                            店舗
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.staffs.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.staffs.*')) active @endif">
                            スタッフ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.additional-items.index') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.additional-items.*')) active @endif">
                            追加項目
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <span class="text-white-50 small text-uppercase px-3">設定</span>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.store-settings.edit') }}" class="p-admin-layout__nav-link nav-link text-white @if(request()->routeIs('admin.store-settings.*')) active @endif">
                            店舗設定
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
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>

        <script defer src="{{ v_asset('assets/js/app.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/alpine/alpine.csp.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/htmx/htmx.min.js') }}"></script>
        @stack('scripts')
    </body>
</html>
