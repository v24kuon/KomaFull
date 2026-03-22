@extends('layouts.member')

@section('title', 'パスワード変更')

@section('content')
<div class="p-member-settings-password">
    <header class="mb-4">
        <nav aria-label="パンくず" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('member.settings.index') }}">会員設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">パスワード</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1">パスワード変更</h1>
        <p class="text-secondary mb-0">現在のパスワードを入力のうえ、新しいパスワードを設定してください。</p>
    </header>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-ui.form-errors />

            <form method="POST" action="{{ route('member.settings.password.update') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                    <x-ui.field-error field="current_password" />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">
                    <x-ui.field-error field="password" />
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">保存する</button>
                    <a class="btn btn-outline-secondary" href="{{ route('member.settings.index') }}">会員設定に戻る</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
