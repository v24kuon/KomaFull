@extends('layouts.app')

@section('title', config('app.name', 'KomaFull').' | 開催枠カレンダー')

@push('styles')
    <link rel="stylesheet" href="{{ v_asset('assets/css/pages/schedule.css') }}">
@endpush

@section('content')
<div
    class="p-schedule py-5"
    x-data="sessionCalendar(@js($calendarPayload))"
>
    <div class="container">
        <header class="mb-4">
            <nav aria-label="パンくず">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ホーム</a></li>
                    <li class="breadcrumb-item active" aria-current="page">開催枠カレンダー</li>
                </ol>
            </nav>
            <h1 class="h2 mb-2">開催枠カレンダー</h1>
            <p class="text-secondary mb-0">
                月ごとの空き状況を記号で表示します。日付を選ぶと、その日の開催枠一覧が下に表示されます（JavaScript 無効時は月の切り替えのみリンクで利用できます）。
            </p>
        </header>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <h2 class="h5 mb-0" x-text="monthLabel"></h2>
            <div class="btn-group" role="group" aria-label="月の切り替え">
                <a class="btn btn-outline-secondary btn-sm" x-bind:href="prevUrl">前月</a>
                <a class="btn btn-outline-secondary btn-sm" x-bind:href="nextUrl">次月</a>
            </div>
        </div>

        <div class="p-schedule__legend card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <p class="small text-secondary mb-2">空き目安（当日の全枠の残席合計）</p>
                <ul class="list-inline small mb-0">
                    <li class="list-inline-item me-3"><span class="p-schedule__sym p-schedule__sym--best" aria-hidden="true">◎</span> 10席以上</li>
                    <li class="list-inline-item me-3"><span class="p-schedule__sym p-schedule__sym--ok" aria-hidden="true">○</span> 4〜9席</li>
                    <li class="list-inline-item me-3"><span class="p-schedule__sym p-schedule__sym--low" aria-hidden="true">△</span> 1〜3席</li>
                    <li class="list-inline-item me-3"><span class="p-schedule__sym p-schedule__sym--full" aria-hidden="true">×</span> 満席</li>
                    <li class="list-inline-item"><span class="text-muted">—</span> 枠なし</li>
                </ul>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered p-schedule__cal text-center mb-0" role="grid" aria-label="開催枠カレンダー">
                <thead>
                    <tr>
                        <template x-for="(label, li) in weekdayLabels" :key="'wd-' + li">
                            <th class="p-schedule__weekday" scope="col" x-text="label"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(week, wi) in weeks" :key="'wk-' + wi">
                        <tr>
                            <template x-for="(cell, di) in week" :key="cell.ymd ? cell.ymd : ('pad-' + wi + '-' + di)">
                                <td
                                    class="p-schedule__cell align-middle"
                                    x-bind:class="{
                                        'p-schedule__cell--muted': !cell.inMonth,
                                        'p-schedule__cell--today': cell.isToday,
                                        'p-schedule__cell--selected': cell.ymd && selectedYmd === cell.ymd
                                    }"
                                >
                                    <template x-if="cell.inMonth">
                                        <button
                                            type="button"
                                            class="p-schedule__daybtn btn btn-link p-2 text-decoration-none"
                                            x-on:click="selectDay(cell)"
                                            x-bind:aria-pressed="cell.ymd && selectedYmd === cell.ymd ? 'true' : 'false'"
                                            x-bind:aria-label="'日付 ' + cell.day + '、空き ' + (cell.symbol || '枠なし')"
                                        >
                                            <span class="d-block fw-semibold" x-text="cell.day"></span>
                                            <span
                                                class="d-block p-schedule__sym mt-1"
                                                x-bind:class="{
                                                    'p-schedule__sym--best': cell.symbol === '◎',
                                                    'p-schedule__sym--ok': cell.symbol === '○',
                                                    'p-schedule__sym--low': cell.symbol === '△',
                                                    'p-schedule__sym--full': cell.symbol === '×'
                                                }"
                                                x-text="cell.symbol ?? '—'"
                                            ></span>
                                        </button>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <section class="p-schedule__daylist card border-0 shadow-sm" aria-live="polite">
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0">日別の開催枠</h2>
            </div>
            <div class="card-body">
                <template x-if="!selectedYmd">
                    <p class="text-secondary mb-0" role="status">日付を選択すると一覧が表示されます。</p>
                </template>
                <template x-if="selectedYmd && selectedSessions.length === 0">
                    <p class="text-secondary mb-0" role="status">この日に表示できる開催枠はありません。</p>
                </template>
                <template x-if="selectedYmd && selectedSessions.length > 0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">開始</th>
                                    <th scope="col">プログラム</th>
                                    <th scope="col">場所</th>
                                    <th scope="col">担当</th>
                                    <th scope="col" class="text-end">残席（一般 / 体験）</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in selectedSessions" :key="row.id">
                                    <tr>
                                        <td x-text="row.timeLabel"></td>
                                        <td>
                                            <a class="link-primary" x-bind:href="row.programUrl" x-text="row.programName"></a>
                                        </td>
                                        <td x-text="row.locationName"></td>
                                        <td x-text="row.staffName"></td>
                                        <td class="text-end">
                                            <span x-text="row.normalRemaining"></span>
                                            <span class="text-muted"> / </span>
                                            <span x-text="row.trialRemaining"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </section>
    </div>
</div>
@endsection
