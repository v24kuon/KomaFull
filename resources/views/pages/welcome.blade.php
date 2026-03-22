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
                <div class="col-lg-7">
                    <p class="p-welcome__eyebrow mb-3">KomaFull MVP</p>
                    <h1 class="display-5 fw-bold mb-3">{{ config('app.name', 'KomaFull') }}</h1>
                    <p class="lead text-secondary mb-4">
                        体験予約から継続利用まで、迷わず進めるレッスン予約プラットフォームです。
                        公開ページ・認証・管理画面を共通レイアウトでそろえ、No-Build の運用方針にも沿った形で公開トップを整えています。
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
                                <span class="badge rounded-pill text-bg-success px-3 py-2">ログイン中</span>
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
                                会員向けダッシュボードは次フェーズで公開予定です。現在は予約導線と認証基盤を順次整備しています。
                            </p>
                        @endcannot
                    @endauth
                </div>

                <div class="col-lg-5">
                    <div class="p-welcome__hero-panel card border-0 shadow-sm">
                        <div class="card-body p-4 p-xl-5">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="p-welcome__metric rounded-4 p-3">
                                        <div class="small text-uppercase text-secondary mb-2">体験導線</div>
                                        <div class="h4 mb-1">メール認証から予約まで</div>
                                        <p class="mb-0 text-secondary">初回体験の申込、認証、決済待機の導線を段階的に提供します。</p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-welcome__metric rounded-4 p-3">
                                        <div class="small text-uppercase text-secondary mb-2">決済</div>
                                        <div class="h5 mb-1">Webhook 正</div>
                                        <p class="mb-0 text-secondary">予約確定や残高付与は Stripe Webhook を正として整合性を保ちます。</p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-welcome__metric rounded-4 p-3">
                                        <div class="small text-uppercase text-secondary mb-2">運用</div>
                                        <div class="h5 mb-1">No-Build</div>
                                        <p class="mb-0 text-secondary">Bootstrap と Alpine を自己ホストし、資産管理をシンプルに保ちます。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <p class="p-welcome__section-label">Guest Flow</p>
                            <h2 class="h4 mb-3">体験予約の入口</h2>
                            <p class="text-secondary mb-0">
                                公開トップから会員登録・ログインへ自然に進める導線を配置し、
                                体験予約フローへの入口として機能させます。
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <p class="p-welcome__section-label">Membership</p>
                            <h2 class="h4 mb-3">継続利用への接続</h2>
                            <p class="text-secondary mb-0">
                                回数券・ポイント・サブスクなど、今後の継続導線につながる情報設計を前提にしたトップページです。
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <p class="p-welcome__section-label">Operations</p>
                            <h2 class="h4 mb-3">管理画面との整合</h2>
                            <p class="text-secondary mb-0">
                                管理者はこの画面からそのまま管理ダッシュボードへ遷移でき、公開側と運用側の境目を分かりやすく保ちます。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.public.site-footer')
</div>
@endsection
