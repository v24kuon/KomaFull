<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <link rel="stylesheet" href="{{ v_asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ v_asset('assets/css/app.css') }}">
        @stack('styles')
    </head>
    <body>
        @yield('content')

        <script defer src="{{ v_asset('assets/vendor/alpine/alpine.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script defer src="{{ v_asset('assets/js/app.js') }}"></script>
        @stack('scripts')
    </body>
</html>
