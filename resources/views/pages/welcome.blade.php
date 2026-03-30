@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | ホーム')

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/welcome.css') }}">
@endpush

@section('content')
<div class="p-welcome">
    <section class="p-welcome__hero py-5">
        <div class="container py-4 py-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7 p-welcome__hero-intro">
                    <p class="p-welcome__eyebrow mb-3">レッスン予約サービス</p>
                    <h1 class="display-5 fw-bold mb-3">{{ config('app.name', 'KomaFull') }}</h1>
                    <p class="lead text-secondary mb-4">
                        体験レッスンは、<strong>体験お申し込み（フォーム送信）→ メール認証 → 仮会員（ログイン可）</strong>の順で進みます。
                        その後、<strong>開催枠の選択</strong>と<strong>お支払い方法（クレジットカード／現地払い）の選択</strong>へ進みます。
                        クレジットカードの場合は決済完了をシステム側で確認したうえで体験予約を確定します。現地払いの場合は、<strong>現地払いを選んだ時点で</strong>体験予約が確定します。
                        カード決済の処理中は枠を確保せず、決済完了後に満席となった場合は<strong>自動返金</strong>し、予約は成立しません。
                        通常のレッスン予約は<strong>本会員のみ</strong>が行え、回数券・ポイント・サブスクリプション（コース）の枠などから消費元をお選びいただけます。
                    </p>

                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <a href="{{ route('schedule.index') }}" class="btn btn-outline-primary btn-lg">開催枠カレンダー</a>
                        <a href="{{ route('programs.index') }}" class="btn btn-outline-primary btn-lg">プログラム一覧</a>
                        @guest
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">会員登録をはじめる</a>
                            @endif
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="btn btn-outline-dark btn-lg">ログイン</a>
                            @endif
                        @else
                            @can('access-admin')
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg">管理ダッシュボードへ</a>
                            @else
                                <a href="{{ route('member.dashboard') }}" class="btn btn-primary btn-lg">マイページ</a>
                            @endcan

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-lg">ログアウト</button>
                            </form>
                        @endguest
                    </div>

                    @auth
                        @cannot('access-admin')
                            <p class="small text-secondary mt-3 mb-0">
                                ご予約状況や回数券・ポイントの残高は <a href="{{ route('member.dashboard') }}">マイページ</a> でご確認いただけます。
                            </p>
                        @endcannot
                    @endauth
                </div>

                <div class="col-lg-5 p-welcome__hero-aside">
                    <div class="p-welcome__hero-panel card border-0 shadow-sm">
                        <div class="card-body p-4 p-xl-5">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="p-welcome__metric rounded-4 p-3 p-welcome__metric--1">
                                        <div class="p-welcome__metric-label mb-2">体験の流れ</div>
                                        <div class="h5 mb-2">フォーム → メール認証 → 仮会員</div>
                                        <p class="mb-0 text-secondary small">
                                            体験お申し込みフォーム送信後、登録メールの認証を行っていただき、仮会員としてログインできるようになります。続いて開催枠を選び、クレジットカードまたは現地払いをお選びください。
                                        </p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-welcome__metric rounded-4 p-3 p-welcome__metric--2">
                                        <div class="p-welcome__metric-label mb-2">体験の確定タイミング</div>
                                        <div class="h6 mb-2">カード／現地で異なります</div>
                                        <p class="mb-0 text-secondary small">
                                            <strong>カード</strong>：決済完了をシステムが確認してから体験予約を確定します（決済中は枠を確保しません）。<strong>現地払い</strong>：現地払いを選んだ時点で体験予約が確定します。カード決済後に満席の場合は自動返金し、予約は作りません。
                                        </p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-welcome__metric rounded-4 p-3 p-welcome__metric--3">
                                        <div class="p-welcome__metric-label mb-2">仮会員と本会員</div>
                                        <div class="h6 mb-2">体験出席で本会員へ</div>
                                        <p class="mb-0 text-secondary small">
                                            体験予約がお済みの時点では仮会員です。体験レッスンに<strong>出席</strong>されたことをもって本会員へ移行します。本会員は、回数券・ポイント・サブスク枠などから消費元を選び通常予約ができます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 p-welcome__sections">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm p-welcome__feature-card">
                        <div class="card-body p-4">
                            <p class="p-welcome__section-label mb-2">はじめての方へ</p>
                            <h2 class="h4 mb-3">体験から利用開始</h2>
                            <p class="text-secondary mb-0">
                                体験は<strong>お申し込みフォーム → メール認証 → 仮会員</strong>のあと、開催枠とお支払い方法を選ぶ流れです（「先に一般会員登録してから体験」ではありません）。
                                プログラムや店舗の雰囲気は
                                <a href="{{ route('programs.index') }}" class="link-primary">プログラム一覧</a>・
                                <a href="{{ route('stores.index') }}" class="link-primary">店舗一覧</a>
                                からご覧いただけます。
                                @guest
                                    @if (Route::has('register'))
                                        お申し込み・仮会員の登録は<a href="{{ route('register') }}" class="link-primary">会員登録</a>から進めます。
                                    @endif
                                @endguest
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm p-welcome__feature-card">
                        <div class="card-body p-4">
                            <p class="p-welcome__section-label mb-2">本会員の方へ</p>
                            <h2 class="h4 mb-3">通常予約と継続利用</h2>
                            <p class="text-secondary mb-0">
                                通常レッスンの予約は<strong>本会員のみ</strong>です。予約時に回数券・ポイント・サブスクリプション（コース）の枠など、<strong>消費元をご自身でお選び</strong>ください。
                                サブスクリプションの付与枠に繰越はなく、周期末で失効します。
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm p-welcome__feature-card">
                        <div class="card-body p-4">
                            <p class="p-welcome__section-label mb-2">スケジュール</p>
                            <h2 class="h4 mb-3">空き状況の確認</h2>
                            <p class="text-secondary mb-0">
                                <a href="{{ route('schedule.index') }}" class="link-primary">開催枠カレンダー</a>で月ごとの空き目安をご確認いただき、日付を選ぶとその日の開催枠一覧へ進めます。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
