@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | プログラム一覧')

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/programs.css') }}">
@endpush

@section('content')
<div class="p-programs py-5">
    <div class="container">
        <header class="mb-4">
            <nav aria-label="パンくず">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">ホーム</a></li>
                    <li class="breadcrumb-item active" aria-current="page">プログラム</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">プログラム一覧</h1>
            <p class="text-secondary mb-0">開講中のプログラムを一覧しています。カードから詳細をモーダルで確認するか、別ページで開いてください。</p>
        </header>

        @include('partials.programs.list', ['programs' => $programs])
    </div>

    <div
        class="modal fade"
        id="programDetailModal"
        tabindex="-1"
        aria-labelledby="programDetailModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0" id="programDetailModalLabel">プログラム詳細</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body" id="programModalBody">
                    <p class="text-secondary mb-0" role="status">一覧からプログラムを選択してください。</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
