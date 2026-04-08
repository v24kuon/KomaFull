@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 通常予約')

@section('content')
<div class="py-5">
    <div class="container" style="max-width: 640px;">
        <nav aria-label="パンくず">
            <ol class="breadcrumb mb-3">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('schedule.index', ['year' => $lessonSession->starts_at->year, 'month' => $lessonSession->starts_at->month]) }}">開催枠</a></li>
                <li class="breadcrumb-item active" aria-current="page">通常予約</li>
            </ol>
        </nav>

        <h1 class="h3 mb-3">通常予約</h1>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 text-secondary">開催枠</h2>
                <p class="mb-1 fw-semibold">{{ $lessonSession->program->name }}</p>
                <p class="mb-0 small text-secondary">
                    {{ $lessonSession->starts_at->timezone(config('app.timezone'))->format('Y年n月j日 H:i') }} 〜
                    · {{ $lessonSession->location->name ?? '—' }}
                </p>
                <p class="small mt-2 mb-0">一般の空き: <strong>{{ $normalRemaining }}</strong> 席</p>
                <hr class="my-3">
                <dl class="row small mb-0">
                    <dt class="col-sm-4">回数券で必要</dt>
                    <dd class="col-sm-8">{{ $program->ticket_cost }} 枚</dd>
                    <dt class="col-sm-4">ポイントで必要</dt>
                    <dd class="col-sm-8">{{ $program->point_cost }} pt</dd>
                </dl>
            </div>
        </div>

        <form method="post" action="{{ route('booking.normal.store') }}" class="card border-0 shadow-sm" x-data="submitState" @submit="startSubmitting">
            @csrf
            <input type="hidden" name="lesson_session_id" value="{{ $lessonSession->id }}">

            <div class="card-body">
                <fieldset>
                    <legend class="h6 mb-3">消費元</legend>

                    @if (count($subscriptionOptions) > 0)
                        <div class="form-check mb-2">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="payment_method"
                                id="pay-subscription"
                                value="subscription"
                                @checked(old('payment_method', count($subscriptionOptions) > 0 ? 'subscription' : '') === 'subscription')
                            >
                            <label class="form-check-label" for="pay-subscription">サブスク枠</label>
                        </div>
                        <div class="ms-4 mb-3">
                            <label for="course_entitlement_id" class="form-label small text-secondary">利用する枠</label>
                            <select
                                name="course_entitlement_id"
                                id="course_entitlement_id"
                                class="form-select form-select-sm @error('course_entitlement_id') is-invalid @enderror"
                            >
                                <option value="">選択してください</option>
                                @foreach ($subscriptionOptions as $opt)
                                    <option value="{{ $opt['course_entitlement_id'] }}" @selected((int) old('course_entitlement_id') === $opt['course_entitlement_id'])>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_entitlement_id')
                                <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="payment_method"
                            id="pay-tickets"
                            value="tickets"
                            @checked(old('payment_method') === 'tickets')
                        >
                        <label class="form-check-label" for="pay-tickets">回数券（残り {{ $ticketBalance }} 枚）</label>
                    </div>

                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="payment_method"
                            id="pay-points"
                            value="points"
                            @checked(old('payment_method') === 'points')
                        >
                        <label class="form-check-label" for="pay-points">ポイント（残り {{ $pointBalance }} pt）</label>
                    </div>
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
                        <span x-show="!submitting">予約を確定する</span>
                        <span x-show="submitting" x-cloak>処理中…</span>
                    </button>
                    <a href="{{ route('schedule.index', ['year' => $lessonSession->starts_at->year, 'month' => $lessonSession->starts_at->month]) }}" class="btn btn-outline-secondary">戻る</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
