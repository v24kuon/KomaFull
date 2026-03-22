@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | お問い合わせ')

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/public-misc.css') }}">
@endpush

@section('content')
<div class="p-contact py-5">
    <div class="container p-public-container--contact">
        <header class="mb-4">
            <nav aria-label="パンくず">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                    <li class="breadcrumb-item active" aria-current="page">お問い合わせ</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">お問い合わせ</h1>
            <p class="text-secondary mb-0">
                ご質問・ご相談は以下のフォームよりお送りください。
            </p>
        </header>

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
            @csrf

            <x-ui.form-errors class="mb-3" />

            <div class="mb-3">
                <label for="name" class="form-label">お名前 <span class="text-danger">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                    maxlength="100"
                >
                <x-ui.field-error field="name" />
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
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
                <label for="phone" class="form-label">電話番号</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    class="form-control @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}"
                    autocomplete="tel"
                    maxlength="50"
                >
                <x-ui.field-error field="phone" />
            </div>

            <div class="mb-3">
                <label for="inquiry_type" class="form-label">お問い合わせ種別 <span class="text-danger">*</span></label>
                <select
                    id="inquiry_type"
                    name="inquiry_type"
                    class="form-select @error('inquiry_type') is-invalid @enderror"
                    required
                >
                    <option value="" disabled @selected(old('inquiry_type') === null || old('inquiry_type') === '')>選択してください</option>
                    <option value="reservation" @selected(old('inquiry_type') === 'reservation')>予約・開催枠</option>
                    <option value="billing" @selected(old('inquiry_type') === 'billing')>決済・請求</option>
                    <option value="account" @selected(old('inquiry_type') === 'account')>会員アカウント</option>
                    <option value="other" @selected(old('inquiry_type') === 'other')>その他</option>
                </select>
                <x-ui.field-error field="inquiry_type" />
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">お問い合わせ内容 <span class="text-danger">*</span></label>
                <textarea
                    id="body"
                    name="body"
                    class="form-control @error('body') is-invalid @enderror"
                    rows="8"
                    required
                    maxlength="5000"
                >{{ old('body') }}</textarea>
                <x-ui.field-error field="body" />
            </div>

            <div class="d-grid">
                <x-ui.submit-button class="btn btn-primary btn-lg">
                    送信する
                </x-ui.submit-button>
            </div>
        </form>
    </div>

    @include('partials.public.site-footer')
</div>
@endsection
