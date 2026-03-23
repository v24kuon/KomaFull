@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 開催枠カレンダー')

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/schedule.css') }}">
@endpush

@section('content')
{{-- 日別一覧・月切替は HTMX で #schedule-partial のみ差し替え（GET、失敗時は href で通常遷移）。 --}}
<div class="p-schedule py-5">
    <div class="container">
        <header class="mb-4">
            <nav aria-label="パンくず">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                    <li class="breadcrumb-item active" aria-current="page">開催枠カレンダー</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">開催枠カレンダー</h1>
            <p class="text-secondary mb-0">
                月ごとの空き目安を記号で表示します。日付を選ぶと、その日の開催枠一覧が下に表示されます。今日より前の日付は予約対象外のため選択できません。
            </p>
        </header>

        <div class="position-relative">
            <div id="schedule-htmx-indicator" class="htmx-indicator small text-muted position-absolute top-0 end-0 py-1" aria-hidden="true">更新中…</div>
            <div id="schedule-partial" class="schedule-partial">
                @include('partials.schedule.interactive')
            </div>
        </div>
    </div>
</div>
@endsection
