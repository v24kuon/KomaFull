@extends('layouts.app')

@section('title', 'パスワード再設定')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-4">新しいパスワードの設定</h1>

                    <form method="POST" action="{{ route('password.update') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="mb-3">
                            <label for="email" class="form-label">メールアドレス</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $request->email) }}"
                                required
                                autocomplete="email"
                                autofocus
                            >
                            <x-ui.field-error field="email" />
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">新しいパスワード</label>
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

                        <div class="d-grid">
                            <x-ui.submit-button>パスワードを再設定</x-ui.submit-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
