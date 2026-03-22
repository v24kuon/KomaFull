@extends('layouts.member')

@section('title', 'マイページ')

@section('content')
<div class="p-member-dashboard">
    <header class="mb-4">
        <h1 class="h3 mb-1">マイページ</h1>
        <p class="text-secondary mb-0">予約の予定と、回数券・ポイント・サブスク枠の残りを一覧できます。</p>
    </header>

    @if ($memberProfile === null)
        <div class="alert alert-info" role="alert">
            会員プロフィールを準備中です。メール認証完了後に自動で作成されます。
        </div>
    @else
        <p class="small text-secondary mb-4">会員番号 <span class="font-monospace">{{ $memberProfile->code }}</span>
            · ステータス
            @if ($memberProfile->member_status === \App\Models\MemberProfile::STATUS_ACTIVE)
                <span class="badge text-bg-success">本会員</span>
            @elseif ($memberProfile->member_status === \App\Models\MemberProfile::STATUS_PROVISIONAL)
                <span class="badge text-bg-secondary">仮会員</span>
            @elseif ($memberProfile->member_status === \App\Models\MemberProfile::STATUS_WITHDRAWN)
                <span class="badge text-bg-dark">退会済み</span>
            @else
                <span class="badge text-bg-light text-dark">{{ $memberProfile->member_status }}</span>
            @endif
        </p>
        <p class="mb-4">
            <a href="{{ route('member.profile.edit') }}">プロフィールを編集</a>
        </p>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-member-dashboard__card">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase mb-2">回数券</h2>
                    <p class="display-6 fw-bold mb-0">{{ number_format($ticketBalance) }} <span class="fs-6 fw-normal text-secondary">枚</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-member-dashboard__card">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase mb-2">ポイント</h2>
                    <p class="display-6 fw-bold mb-0">{{ number_format($pointBalance) }} <span class="fs-6 fw-normal text-secondary">pt</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-member-dashboard__card">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase mb-2">サブスク枠（今周期）</h2>
                    @if ($subscriptionEntitlements->isEmpty())
                        <p class="mb-0 text-secondary">有効な枠はありません</p>
                    @else
                        <ul class="list-unstyled mb-0 small">
                            @foreach ($subscriptionEntitlements as $entitlement)
                                @php
                                    $remaining = max(0, $entitlement->granted_uses - $entitlement->used_uses);
                                @endphp
                                <li class="mb-2">
                                    <span class="fw-semibold">{{ $entitlement->coursePlan?->name ?? 'プラン' }}</span>
                                    <span class="text-secondary">残り {{ $remaining }} / {{ $entitlement->granted_uses }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <section class="p-member-dashboard__reservations">
        <h2 class="h5 mb-3">これからの予約</h2>
        @if ($upcomingReservations->isEmpty())
            <p class="text-secondary mb-0">今後の確定予約はありません。</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">日時</th>
                            <th scope="col">プログラム</th>
                            <th scope="col">店舗</th>
                            <th scope="col">区分</th>
                            <th scope="col">支払い</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($upcomingReservations as $reservation)
                            @php
                                $session = $reservation->lessonSession;
                                $seatLabel = $reservation->seat_bucket === \App\Models\Reservation::SEAT_BUCKET_TRIAL ? '体験' : '一般';
                                $paymentLabels = [
                                    \App\Models\Reservation::PAYMENT_METHOD_SUBSCRIPTION => 'サブスク',
                                    \App\Models\Reservation::PAYMENT_METHOD_TICKETS => '回数券',
                                    \App\Models\Reservation::PAYMENT_METHOD_POINTS => 'ポイント',
                                    \App\Models\Reservation::PAYMENT_METHOD_TRIAL_CARD => '体験（カード）',
                                    \App\Models\Reservation::PAYMENT_METHOD_TRIAL_ONSITE => '体験（現地）',
                                ];
                                $payLabel = $paymentLabels[$reservation->payment_method] ?? $reservation->payment_method;
                            @endphp
                            <tr>
                                <td>
                                    @if ($session)
                                        <time datetime="{{ $session->starts_at->toIso8601String() }}">
                                            {{ $session->starts_at->timezone(config('app.timezone'))->format('Y/m/d H:i') }}
                                        </time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $session?->program?->name ?? '—' }}</td>
                                <td>{{ $session?->location?->name ?? '—' }}</td>
                                <td>{{ $seatLabel }}</td>
                                <td>{{ $payLabel }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
