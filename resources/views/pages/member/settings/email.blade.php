@extends('layouts.member')

@section('title', 'メールアドレス変更')

@section('content')
<div class="p-member-settings-email">
    <header class="mb-4">
        <nav aria-label="パンくず" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('member.settings.index') }}">会員設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">メールアドレス</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1">メールアドレス変更</h1>
        <p class="text-secondary mb-0">現在のパスワードを確認し、新しいメールアドレスを入力してください。変更後、確認メールが届きます。</p>
    </header>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <x-ui.form-errors />

            <form method="POST" action="{{ route('member.settings.email.update') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                    <x-ui.field-error field="current_password" />
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label">新しいメールアドレス <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                    <x-ui.field-error field="email" />
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
