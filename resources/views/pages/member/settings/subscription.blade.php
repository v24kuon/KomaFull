@extends('layouts.member')

@section('title', 'サブスク管理')

@section('content')
<div class="p-member-settings-subscription">
    <header class="mb-4">
        <nav aria-label="パンくず" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('member.settings.index') }}">会員設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">サブスク管理</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1">サブスク管理</h1>
        <p class="text-secondary mb-0">プランの変更や、請求期間末での解約を行えます。</p>
    </header>

    @if (! $hasActiveLikeSubscription)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="mb-0 text-secondary">現在、有効なサブスクリプションはありません。プランのご契約後にこちらから変更・解約の手続きができます。</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 card-title">ご契約中のプラン</h2>
                <p class="mb-1">
                    <span class="fw-semibold">{{ $currentPlan?->name ?? 'プラン（マスタ未登録の料金）' }}</span>
                </p>
                @if ($subscription->onTrial())
                    <p class="small text-secondary mb-0">トライアル中です。</p>
                @elseif ($subscription->onGracePeriod())
                    <p class="small text-warning mb-0">
                        <span class="fw-semibold">解約予約中</span>です。解約予定日（{{ $subscription->ends_at?->timezone(config('app.timezone'))?->format('Y/m/d') }}）までご利用いただけます。
                    </p>
                @elseif ($subscriptionCurrentPeriodEnd)
                    <p class="small text-secondary mb-0">
                        次回請求日の目安: {{ $subscriptionCurrentPeriodEnd->timezone(config('app.timezone'))->format('Y/m/d') }}
                    </p>
                @endif
            </div>
        </div>

        @if ($canResume)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 card-title">解約の取り消し</h2>
                    <p class="card-text small text-secondary mb-3">解約予約を取り消し、継続課金を再開します。</p>
                    <x-ui.form-errors bag="resume" />
                    <form method="POST" action="{{ route('member.settings.subscription.resume') }}" class="d-inline" x-data="submitState()" x-on:submit="startSubmitting($event)">
                        @csrf
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input @error('resume_confirmed', 'resume') is-invalid @enderror" id="resume_confirmed" name="resume_confirmed" value="1" {{ old('resume_confirmed') ? 'checked' : '' }}>
                            <label class="form-check-label" for="resume_confirmed">解約予約を取り消し、継続する</label>
                            <x-ui.field-error field="resume_confirmed" bag="resume" />
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" data-testid="subscription-resume-button">解約を取り消す</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($canSwap && $swapCandidates->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 card-title">プラン変更</h2>
                    <p class="card-text small text-secondary mb-3">別のプランへ変更します。差額の扱いは Stripe の設定に従います。</p>
                    <x-ui.form-errors bag="swap" />
                    <form method="POST" action="{{ route('member.settings.subscription.swap') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                        @csrf
                        <div class="mb-3">
                            <label for="stripe_price_id" class="form-label">変更先プラン</label>
                            <select name="stripe_price_id" id="stripe_price_id" class="form-select @error('stripe_price_id', 'swap') is-invalid @enderror" required data-testid="subscription-swap-select">
                                <option value="" disabled {{ old('stripe_price_id') ? '' : 'selected' }}>選択してください</option>
                                @foreach ($swapCandidates as $plan)
                                    <option value="{{ $plan->stripe_price_id }}" {{ old('stripe_price_id') === $plan->stripe_price_id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-ui.field-error field="stripe_price_id" bag="swap" />
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" data-testid="subscription-swap-submit">プランを変更する</button>
                    </form>
                </div>
            </div>
        @elseif ($canSwap && $swapCandidates->isEmpty())
            <div class="alert alert-secondary mb-4" role="status">
                現在、変更可能なプランがマスタに登録されていません。お問い合わせください。
            </div>
        @endif

        @if ($canCancel)
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 card-title text-danger">請求期間末での解約</h2>
                    <p class="card-text small text-secondary mb-3">いまの請求期間の終了日に解約するよう予約します。期間内は利用可能です。</p>
                    <x-ui.form-errors bag="cancel" />
                    <form method="POST" action="{{ route('member.settings.subscription.cancel') }}" x-data="submitState()" x-on:submit="startSubmitting($event)">
                        @csrf
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input @error('cancellation_confirmed', 'cancel') is-invalid @enderror" id="cancellation_confirmed" name="cancellation_confirmed" value="1" {{ old('cancellation_confirmed') ? 'checked' : '' }}>
                            <label class="form-check-label" for="cancellation_confirmed">請求期間末に解約する内容を理解した</label>
                            <x-ui.field-error field="cancellation_confirmed" bag="cancel" />
                        </div>
                        <button type="submit" class="btn btn-outline-danger btn-sm" data-testid="subscription-cancel-button">請求期間末で解約する</button>
                    </form>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
