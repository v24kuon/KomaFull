@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 体験予約')

@section('content')
<div class="py-5">
    <div class="container" style="max-width: 640px;">
        <nav aria-label="パンくず">
            <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('schedule.index', ['year' => $lessonSession->starts_at->year, 'month' => $lessonSession->starts_at->month]) }}">開催枠</a></li>
                <li class="breadcrumb-item active" aria-current="page">体験予約</li>
            </ol>
        </nav>

        <h1 class="h3 mb-3">体験レッスン予約</h1>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 text-secondary">開催枠</h2>
                <p class="mb-1 fw-semibold">{{ $lessonSession->program->name }}</p>
                <p class="mb-0 small text-secondary">
                    {{ $lessonSession->starts_at->timezone(config('app.timezone'))->format('Y年n月j日 H:i') }} 〜
                    · {{ $lessonSession->location->name ?? '—' }}
                    @if ($lessonSession->staff)
                        · {{ $lessonSession->staff->name }}
                    @endif
                </p>
                <p class="small mt-2 mb-0">体験の空き: <strong>{{ $trialRemaining }}</strong> 席</p>
            </div>
        </div>

        <form method="post" action="{{ route('booking.trial.store') }}" class="card border-0 shadow-sm" x-data="submitState" @submit="startSubmitting">
            @csrf
            <input type="hidden" name="lesson_session_id" value="{{ $lessonSession->id }}">

            <div class="card-body">
                <fieldset>
                    <legend class="h6 mb-3">支払い方法</legend>

                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="payment_method"
                            id="pay-card"
                            value="card"
                            @checked(old('payment_method', 'card') === 'card')
                        >
                        <label class="form-check-label" for="pay-card">カード（Stripe Checkout）</label>
                    </div>
                    @if ($lessonSession->program->price > 0)
                        <p class="small text-secondary ms-4 mb-3">お支払い金額: <strong>{{ number_format($lessonSession->program->price) }}</strong> {{ strtoupper(config('cashier.currency')) }}</p>
                    @endif

                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="payment_method"
                            id="pay-onsite"
                            value="onsite"
                            @checked(old('payment_method') === 'onsite')
                        >
                        <label class="form-check-label" for="pay-onsite">現地払い（この時点で確定）</label>
                    </div>
                    <p class="small text-secondary ms-4 mb-0">現地払いを選ぶと、決済画面に進まずに予約が確定します。</p>
                </fieldset>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" :disabled="submitting" :aria-busy="submitting ? 'true' : 'false'">
                        <span x-show="!submitting">次へ</span>
                        <span x-show="submitting" x-cloak>処理中…</span>
                    </button>
                    <a href="{{ route('schedule.index', ['year' => $lessonSession->starts_at->year, 'month' => $lessonSession->starts_at->month]) }}" class="btn btn-outline-secondary">戻る</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
