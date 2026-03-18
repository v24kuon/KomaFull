@extends('layouts.admin')

@section('page-title', 'ダッシュボード')

@section('content')
@php
    $summaryCardLabels = [
        '本日の予約',
        '本日のセッション',
        '会員数',
        'Webhook異常',
    ];
@endphp
<div class="row g-4">
    @foreach ($summaryCardLabels as $summaryCardLabel)
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-body-secondary">{{ $summaryCardLabel }}</h6>
                    <p class="card-text fs-3 fw-bold mb-0">-</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
