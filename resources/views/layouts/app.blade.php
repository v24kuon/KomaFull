<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'KomaFull'))</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@500;700&family=Noto+Sans+JP:wght@400;500;600&display=swap">

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/app.css') }}">
        @stack('styles')
    </head>
    <body class="d-flex flex-column min-vh-100" hx-headers='{"X-CSRF-TOKEN":"{{ csrf_token() }}"}'>
        @include('partials.public.site-header')

        @if (session('success'))
            <div class="container pt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="container pt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
                </div>
            </div>
        @endif

        <main id="main-content" class="flex-grow-1">
            @yield('content')
        </main>

        @include('partials.public.site-footer')

        <script defer src="{{ v_asset('assets/js/app.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/alpine/alpine.csp.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/htmx/htmx.min.js') }}"></script>
        @stack('scripts')
    </body>
</html>
