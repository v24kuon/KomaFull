@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 店舗一覧')

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
                    <li class="breadcrumb-item active" aria-current="page">店舗</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">店舗一覧</h1>
            <p class="text-secondary mb-0">
                各店舗の所在地・連絡先をご確認いただけます。
            </p>
        </header>

        @if ($locations->isEmpty())
            <p class="text-secondary mb-0" role="status">現在表示できる店舗はありません。</p>
        @else
            <ul class="list-group list-group-flush shadow-sm rounded border">
                @foreach ($locations as $location)
                    <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3">
                        <div>
                            <h2 class="h5 mb-1">
                                <a href="{{ route('stores.show', $location) }}">{{ $location->name }}</a>
                            </h2>
                            @if ($location->address)
                                <p class="mb-0 text-secondary small">{{ $location->address }}</p>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('stores.show', $location) }}" class="btn btn-outline-primary btn-sm">詳細</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @include('partials.public.site-footer')
</div>
@endsection
