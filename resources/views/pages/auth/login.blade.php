@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-4">ログイン</h1>

                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">メールアドレス</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">パスワード</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="form-check-input"
                                @checked(old('remember'))
                            >
                            <label for="remember" class="form-check-label">ログイン状態を保持する</label>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">ログイン</button>
                        </div>

                        <div class="text-center">
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="d-block mb-2">パスワードをお忘れですか？</a>
                            @endif

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}">アカウントをお持ちでない方はこちら</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
