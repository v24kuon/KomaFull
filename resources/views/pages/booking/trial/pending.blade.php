@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 決済確認中')

@section('content')
<div class="py-5">
    <div class="container" style="max-width: 640px;">
        <h1 class="h3 mb-3">枠を確定しています</h1>

        @isset($loadError)
            <div class="alert alert-danger" role="alert">{{ $loadError }}</div>
            <p class="mb-0"><a href="{{ route('member.dashboard') }}">マイページへ</a></p>
        @else
            <div
                class="card border-0 shadow-sm"
                x-data="{{ $bookingPendingXData }}"
            >
                <div class="card-body">
                    <p class="mb-2">
                        Stripe の決済は完了しています。予約の確定はサーバー側の処理が完了するまでお待ちください（通常は数秒〜数十秒です）。
                    </p>
                    <p class="small text-secondary mb-3" role="status" x-text="statusLabel"></p>
                    <div class="d-flex align-items-center gap-2" x-show="!stopped" aria-live="polite">
                        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                        <span>確認中…</span>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0" role="alert" x-show="terminalMessage !== ''" x-text="terminalMessage" x-cloak></div>
                </div>
            </div>
        @endisset
    </div>
</div>
@endsection
