<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'KomaFull'))</title>

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/app.css') }}">
        @stack('styles')
    </head>
    <body hx-headers='{"X-CSRF-TOKEN":"{{ csrf_token() }}"}'>
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
        @yield('content')

        <script defer src="{{ v_asset('assets/js/app.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/alpine/alpine.csp.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/htmx/htmx.min.js') }}"></script>
        @stack('scripts')
    </body>
</html>
