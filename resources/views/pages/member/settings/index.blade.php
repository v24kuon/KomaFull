@extends('layouts.member')

@section('title', '会員設定')

@section('content')
<div class="p-member-settings">
    <header class="mb-4">
        <h1 class="h3 mb-1">会員設定</h1>
        <p class="text-secondary mb-0">ログイン情報・お支払いカード・退会はこちらから行えます。</p>
    </header>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 card-title">パスワード</h2>
                    <p class="card-text small text-secondary mb-3">ログイン中のパスワードを変更します。</p>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('member.settings.password.edit') }}">パスワードを変更</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 card-title">メールアドレス</h2>
                    <p class="card-text small text-secondary mb-3">変更後、新しいアドレス宛に確認メールが届きます。</p>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('member.settings.email.edit') }}">メールアドレスを変更</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 card-title">お支払いカード</h2>
                    <p class="card-text small text-secondary mb-3">Stripe の安全な画面でカード情報の更新・削除ができます。</p>
                    <form method="POST" action="{{ route('member.settings.billing-portal') }}" class="d-inline" x-data="submitState()" x-on:submit="startSubmitting($event)">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm" data-testid="billing-portal-button">カード情報を管理</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 card-title">退会</h2>
                    <p class="card-text small text-secondary mb-3">退会後はマイページをご利用いただけません。</p>
                    <a class="btn btn-outline-danger btn-sm" href="{{ route('member.settings.withdraw.edit') }}">退会手続き</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
