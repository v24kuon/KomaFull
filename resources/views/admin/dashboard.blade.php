@extends('layouts.admin')

@section('page-title', 'ダッシュボード')

@section('content')
<div class="row g-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-body-secondary">本日の予約</h6>
                <p class="card-text fs-3 fw-bold mb-0">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-body-secondary">本日のセッション</h6>
                <p class="card-text fs-3 fw-bold mb-0">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-body-secondary">会員数</h6>
                <p class="card-text fs-3 fw-bold mb-0">-</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-body-secondary">Webhook異常</h6>
                <p class="card-text fs-3 fw-bold mb-0">-</p>
            </div>
        </div>
    </div>
</div>
@endsection
