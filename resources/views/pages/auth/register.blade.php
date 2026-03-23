@extends('layouts.app')

@section('title', '会員登録')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-4">会員登録</h1>
                    <p class="small text-secondary text-center mb-4">
                        登録後にメール認証へ進み、認証完了後に仮会員としてログインできます。続く体験の手順では、開催枠の選択とお支払い方法（クレジットカード／現地払い）をお選びください。
                    </p>

                    <form method="POST" action="{{ route('register') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">お名前</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}"
                                required
                                autocomplete="name"
                                autofocus
                            >
                            <x-ui.field-error field="name" />
                        </div>

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
                            >
                            <x-ui.field-error field="email" />
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">パスワード</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                            >
                            <x-ui.field-error field="password" />
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">パスワード（確認）</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="d-grid mb-3">
                            <x-ui.submit-button>登録する</x-ui.submit-button>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('login') }}">すでにアカウントをお持ちの方はこちら</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
