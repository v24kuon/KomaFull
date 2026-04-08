{{-- HTMX で #schedule-partial の innerHTML を差し替える用（レイアウト・DOCTYPE なし） --}}
@php
    $scheduleIndexBase = ['year' => $calendarPayload['year'], 'month' => $calendarPayload['month']];
@endphp
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <h2 class="h5 mb-0">{{ $calendarPayload['monthLabel'] }}</h2>
    <div class="btn-group" role="group" aria-label="月の切り替え">
        @if ($schedulePrevUrl)
            <a
                class="btn btn-outline-secondary btn-sm"
                href="{{ $schedulePrevUrl }}"
                hx-get="{{ $schedulePrevUrl }}"
                hx-target="#schedule-partial"
                hx-swap="innerHTML"
                hx-push-url="true"
                hx-indicator="#schedule-htmx-indicator"
            >前月</a>
        @else
            <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">前月</span>
        @endif
        @if ($scheduleNextUrl)
            <a
                class="btn btn-outline-secondary btn-sm"
                href="{{ $scheduleNextUrl }}"
                hx-get="{{ $scheduleNextUrl }}"
                hx-target="#schedule-partial"
                hx-swap="innerHTML"
                hx-push-url="true"
                hx-indicator="#schedule-htmx-indicator"
            >次月</a>
        @else
            <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true">次月</span>
        @endif
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
                @foreach ($calendarPayload['weekdayLabels'] as $label)
                    <th class="p-schedule__weekday" scope="col">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($calendarPayload['weeks'] as $week)
                <tr>
                    @foreach ($week as $cell)
                        @php
                            $inMonth = ! empty($cell['inMonth']);
                            $ymd = $cell['ymd'] ?? null;
                            $sym = $cell['symbol'] ?? null;
                            $symClass = match ($sym) {
                                '◎' => 'p-schedule__sym--best',
                                '○' => 'p-schedule__sym--ok',
                                '△' => 'p-schedule__sym--low',
                                '×' => 'p-schedule__sym--full',
                                default => '',
                            };
                            $isPastDay = $inMonth && $ymd !== null && $ymd < $todayYmd;
                            $isSelectable = $inMonth && $ymd !== null && ! $isPastDay;
                            $isSelected = $isSelectable && $selectedYmd === $ymd;
                            $dayHref = $isSelectable
                                ? ($isSelected
                                    ? route('schedule.index', $scheduleIndexBase)
                                    : route('schedule.index', array_merge($scheduleIndexBase, ['selected' => $ymd])))
                                : null;
                        @endphp
                        <td
                            @class([
                                'p-schedule__cell' => true,
                                'align-middle' => true,
                                'p-schedule__cell--muted' => ! $inMonth,
                                'p-schedule__cell--past' => $isPastDay,
                                'p-schedule__cell--today' => $inMonth && ! empty($cell['isToday']),
                                'p-schedule__cell--selected' => $isSelected,
                            ])
                        >
                            @if ($inMonth && $ymd !== null && $isPastDay)
                                <span
                                    class="p-schedule__past d-block p-2 text-center"
                                    aria-label="日付 {{ $cell['day'] }}、空き {{ $sym ?? '枠なし' }}（過去のため選択できません）"
                                >
                                    <span class="d-block p-schedule__past-day">{{ $cell['day'] }}</span>
                                    <span class="d-block p-schedule__past-sym mt-1">{{ $sym ?? '—' }}</span>
                                </span>
                            @elseif ($inMonth && $ymd !== null && $dayHref !== null)
                                <a
                                    href="{{ $dayHref }}"
                                    hx-get="{{ $dayHref }}"
                                    hx-target="#schedule-partial"
                                    hx-swap="innerHTML"
                                    hx-push-url="true"
                                    hx-indicator="#schedule-htmx-indicator"
                                    class="p-schedule__daybtn btn btn-link p-2 text-decoration-none"
                                    @if ($isSelected) aria-current="date" @endif
                                    aria-label="日付 {{ $cell['day'] }}、空き {{ $sym ?? '枠なし' }}"
                                >
                                    <span class="d-block fw-semibold">{{ $cell['day'] }}</span>
                                    <span class="d-block p-schedule__sym mt-1 {{ $symClass }}">{{ $sym ?? '—' }}</span>
                                </a>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<section class="p-schedule__daylist card border-0 shadow-sm" aria-live="polite">
    <div class="card-header bg-white py-3">
        <h2 class="h6 mb-0">日別の開催枠</h2>
    </div>
    <div class="card-body">
        @if ($selectedYmd === null)
            <p class="text-secondary mb-0" role="status">日付を選択すると一覧が表示されます。</p>
        @elseif (! empty($calendarPayload['sessionsByDay'][$selectedYmd]))
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">開始</th>
                            <th scope="col">プログラム</th>
                            <th scope="col">場所</th>
                            <th scope="col">担当</th>
                            <th scope="col" class="text-end">残席（一般 / 体験）</th>
                            <th scope="col" class="text-end">予約</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($calendarPayload['sessionsByDay'][$selectedYmd] as $row)
                            <tr>
                                <td>{{ $row['timeLabel'] }}</td>
                                <td>
                                    <a class="link-primary" href="{{ $row['programUrl'] }}">{{ $row['programName'] }}</a>
                                </td>
                                <td>{{ $row['locationName'] }}</td>
                                <td>{{ $row['staffName'] }}</td>
                                <td class="text-end">
                                    {{ $row['normalRemaining'] }}
                                    <span class="text-muted"> / </span>
                                    {{ $row['trialRemaining'] }}
                                </td>
                                <td class="text-end small">
                                    @auth
                                        @php
                                            $mp = auth()->user()->memberProfile;
                                        @endphp
                                        @if ($mp !== null && $mp->member_status === \App\Models\MemberProfile::STATUS_PROVISIONAL && $row['trialRemaining'] > 0)
                                            <a class="d-inline-block mb-1" href="{{ route('booking.trial.show', $row['sessionCode']) }}">体験</a>
                                        @endif
                                        @if ($mp !== null && $mp->member_status === \App\Models\MemberProfile::STATUS_ACTIVE && $row['normalRemaining'] > 0)
                                            <a class="d-inline-block mb-1" href="{{ route('booking.normal.show', $row['sessionCode']) }}">通常</a>
                                        @endif
                                        @if ($mp === null)
                                            <span class="text-muted">—</span>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}">ログイン</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-secondary mb-0" role="status">この日に表示できる開催枠はありません。</p>
        @endif
    </div>
</section>
