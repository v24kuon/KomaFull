@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 特定商取引法に基づく表記')

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/public-misc.css') }}">
@endpush

@section('content')
<div class="p-legal py-5">
    <div class="container p-public-container--legal">
        <header class="mb-4">
            <nav aria-label="パンくず">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                    <li class="breadcrumb-item active" aria-current="page">特定商取引法に基づく表記</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">特定商取引法に基づく表記</h1>
            <p class="text-secondary mb-0">
                以下はサービス提供にあたっての表記です。運用に合わせて管理画面・設定で更新する場合は、本ページの文言もあわせて整備してください。
            </p>
        </header>

        <div class="table-responsive shadow-sm rounded border bg-body">
            <table class="table mb-0">
                <tbody>
                    <tr>
                        <th class="text-nowrap bg-body-secondary w-25" scope="row">事業者名</th>
                        <td>{{ config('app.name', 'KomaFull') }}</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">運営責任者</th>
                        <td>（運営者名を記載してください）</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">所在地</th>
                        <td>
                            @if ($primaryLocation?->address)
                                {{ $primaryLocation->address }}
                            @else
                                （店舗マスタに住所を登録するか、ここに直接記載してください）
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">電話番号</th>
                        <td>
                            @if ($primaryLocation?->tel)
                                {{ $primaryLocation->tel }}
                            @else
                                （店舗マスタに電話番号を登録するか、ここに直接記載してください）
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">メールアドレス</th>
                        <td>
                            @if ($primaryLocation?->email)
                                <a href="mailto:{{ $primaryLocation->email }}">{{ $primaryLocation->email }}</a>
                            @else
                                （店舗マスタにメールを登録するか、ここに直接記載してください）
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">販売価格</th>
                        <td>各プログラム・商品ページに表示する価格（税込の旨を明記してください）</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">商品代金以外の必要料金</th>
                        <td>インターネット接続料金その他、利用環境に応じてお客様が負担する費用</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">支払方法</th>
                        <td>クレジットカード決済、現地払い（対象サービスがある場合）など、提供する方法を記載してください</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">支払時期</th>
                        <td>各決済手段に応じた引き落としタイミング・現地払いのタイミングを記載してください</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">サービス提供時期</th>
                        <td>予約確定後、各レッスン開催日時に提供します</td>
                    </tr>
                    <tr>
                        <th class="text-nowrap bg-body-secondary" scope="row">返品・キャンセル</th>
                        <td>
                            キャンセル条件・期限は店舗設定の締切に従います。詳細は利用規約・FAQに記載してください。
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
