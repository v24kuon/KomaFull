@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | '.$location->name)

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/public-misc.css') }}">
@endpush

@section('content')
<div class="p-stores py-5">
    <div class="container">
        <header class="mb-4">
            <nav aria-label="パンくず">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('stores.index') }}">店舗</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $location->name }}</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">{{ $location->name }}</h1>
        </header>

        @if (! $location->address && ! $location->tel && ! $location->email && ! $location->description)
            <p class="text-secondary mb-0" role="status">店舗の詳細情報は準備中です。</p>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        @if ($location->address)
                            <dt class="col-sm-3">住所</dt>
                            <dd class="col-sm-9">{{ $location->address }}</dd>
                        @endif
                        @if ($location->tel)
                            <dt class="col-sm-3">電話</dt>
                            <dd class="col-sm-9"><a href="tel:{{ preg_replace('/\s+/', '', $location->tel) }}">{{ $location->tel }}</a></dd>
                        @endif
                        @if ($location->email)
                            <dt class="col-sm-3">メール</dt>
                            <dd class="col-sm-9"><a href="mailto:{{ $location->email }}">{{ $location->email }}</a></dd>
                        @endif
                    </dl>
                    @if ($location->description)
                        <div class="mt-4 pt-3 border-top">
                            <h2 class="h6 text-secondary">店舗について</h2>
                            <p class="mb-0">{{ $location->description }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <p class="mt-4 mb-0">
            <a href="{{ route('stores.index') }}" class="link-primary">店舗一覧へ戻る</a>
        </p>
    </div>
</div>
@endsection
