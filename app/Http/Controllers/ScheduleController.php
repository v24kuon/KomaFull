<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleIndexRequest;
use App\Models\LessonSession;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * 公開の開催枠カレンダー（月単位）と、日別の枠一覧用データを表示する。
     *
     * 前提: `lesson_sessions.status = active` かつ紐づく `programs.status = active` のみ。
     * 空き記号は当日の全枠の残席合計（一般＋体験）から算出する。
     * 更新方針: 読み取り専用。月はクエリ `year` / `month` で指定（未指定は現在月）。
     * 前月・次月リンクは `ScheduleIndexRequest` の年範囲外へ跨ぐ場合は `null` とし、ビューで無効表示する。
     */
    public function index(ScheduleIndexRequest $request): View
    {
        $validated = $request->validated();
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $sessions = LessonSession::query()
            ->where('lesson_sessions.status', LessonSession::STATUS_ACTIVE)
            ->whereBetween('lesson_sessions.starts_at', [$monthStart, $monthEnd])
            ->whereHas('program', function ($q): void {
                $q->where('programs.status', Program::STATUS_ACTIVE);
            })
            ->with([
                'program',
                'location',
                'staff',
                'reservationManagement',
            ])
            ->orderBy('lesson_sessions.starts_at')
            ->get();

        $tz = config('app.timezone');
        $sessionsByDay = [];
        $totalsByDay = [];

        foreach ($sessions as $session) {
            /** @var Carbon $starts */
            $starts = $session->starts_at->copy()->timezone($tz);
            $ymd = $starts->format('Y-m-d');
            $totalsByDay[$ymd] = ($totalsByDay[$ymd] ?? 0) + $this->totalRemainingSeats($session);
            if (! isset($sessionsByDay[$ymd])) {
                $sessionsByDay[$ymd] = [];
            }
            $sessionsByDay[$ymd][] = $this->serializeSessionRow($session, $starts);
        }

        $weeks = $this->buildCalendarWeeks($year, $month, $totalsByDay, $tz);

        $prev = $monthStart->copy()->subMonth();
        $next = $monthStart->copy()->addMonth();

        $schedulePrevUrl = $prev->year >= ScheduleIndexRequest::MIN_YEAR
            ? route('schedule.index', ['year' => $prev->year, 'month' => $prev->month])
            : null;
        $scheduleNextUrl = $next->year <= ScheduleIndexRequest::MAX_YEAR
            ? route('schedule.index', ['year' => $next->year, 'month' => $next->month])
            : null;

        $calendarPayload = [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $monthStart->copy()->locale('ja')->translatedFormat('Y年n月'),
            'weekdayLabels' => ['月', '火', '水', '木', '金', '土', '日'],
            'weeks' => $weeks,
            'sessionsByDay' => $sessionsByDay,
        ];

        return view('pages.schedule.index', [
            'calendarPayload' => $calendarPayload,
            'schedulePrevUrl' => $schedulePrevUrl,
            'scheduleNextUrl' => $scheduleNextUrl,
        ]);
    }

    /**
     * @param  array<string, int>  $totalsByDay
     * @return list<list<array{ymd: string|null, day: int|null, inMonth: bool, symbol: string|null, isToday: bool, hasSessions: bool}>>
     */
    private function buildCalendarWeeks(int $year, int $month, array $totalsByDay, string $timezone): array
    {
        $first = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $last = $first->copy()->endOfMonth();
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $last->copy()->endOfWeek(Carbon::SUNDAY);

        $today = Carbon::now($timezone)->startOfDay();
        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $inMonth = $cursor->month === $month;
                $ymd = $cursor->format('Y-m-d');
                $hasSessions = $inMonth && isset($totalsByDay[$ymd]);
                $total = $hasSessions ? $totalsByDay[$ymd] : 0;
                $symbol = $inMonth ? $this->daySymbolForTotal($total, $hasSessions) : null;

                $week[] = [
                    'ymd' => $inMonth ? $ymd : null,
                    'day' => $inMonth ? (int) $cursor->format('j') : null,
                    'inMonth' => $inMonth,
                    'symbol' => $symbol,
                    'isToday' => $cursor->equalTo($today),
                    'hasSessions' => $hasSessions,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * 当日のカレンダー用に、残席合計から空き記号（◎ / ○ / △ / ×）を返す。
     *
     * 前提: `$hasSessions` は、その日に掲載対象の `lesson_sessions` が 1 件以上あるとき true。記号の閾値は公開ビューの凡例（`resources/views/pages/schedule/index.blade.php`）と同じ定義とする（合計残席 10 以上 / 4〜9 / 1〜3 / 0）。
     * 更新方針: 閾値や記号の意味を変える場合は凡例テキストと同じコミットで揃える。日別一覧の「一般/体験」内訳とは独立（ここは日単位の合計のみ）。
     *
     * @param  int  $total  当日の全枠の残席合計（一般＋体験）
     * @param  bool  $hasSessions  その日に表示対象の枠が存在するか
     */
    private function daySymbolForTotal(int $total, bool $hasSessions): ?string
    {
        if (! $hasSessions) {
            return null;
        }

        if ($total <= 0) {
            return '×';
        }

        if ($total >= 10) {
            return '◎';
        }

        if ($total >= 4) {
            return '○';
        }

        return '△';
    }

    /**
     * 1 開催枠あたりの一般枠・体験枠の残席合計を返す。
     *
     * 前提: `reservation_management` はカウンタキャッシュであり、真実は `reservations` だが本画面は読み取りのみ。`reservationManagement` が未ロードまたは欠損時は予約 0 件として `capacity` / `trial_capacity` から算出する。
     * 更新方針: 一般・体験の定義やロック順序を変える場合は `ReservationService` 等のドメイン方針と整合させ、日別一覧の `serializeSessionRow` の計算とも揃える。
     */
    private function totalRemainingSeats(LessonSession $session): int
    {
        $rm = $session->reservationManagement;
        $reservedNormal = $rm?->reserved_count ?? 0;
        $reservedTrial = $rm?->reserved_trial_count ?? 0;
        $normal = max(0, $session->capacity - $reservedNormal);
        $trial = max(0, $session->trial_capacity - $reservedTrial);

        return $normal + $trial;
    }

    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     startsAtIso: string,
     *     timeLabel: string,
     *     programName: string,
     *     programUrl: string,
     *     locationName: string,
     *     staffName: string,
     *     normalRemaining: int,
     *     trialRemaining: int,
     *     totalRemaining: int
     * }
     */
    private function serializeSessionRow(LessonSession $session, Carbon $startsLocal): array
    {
        $rm = $session->reservationManagement;
        $reservedNormal = $rm?->reserved_count ?? 0;
        $reservedTrial = $rm?->reserved_trial_count ?? 0;
        $normalRemaining = max(0, $session->capacity - $reservedNormal);
        $trialRemaining = max(0, $session->trial_capacity - $reservedTrial);
        $totalRemaining = $normalRemaining + $trialRemaining;

        $program = $session->program;
        $programUrl = $program instanceof Program
            ? route('programs.show', $program)
            : '#';

        return [
            'id' => $session->id,
            'code' => $session->code,
            'startsAtIso' => $startsLocal->toIso8601String(),
            'timeLabel' => $startsLocal->format('H:i'),
            'programName' => $program?->name ?? '—',
            'programUrl' => $programUrl,
            'locationName' => $session->location?->name ?? '—',
            'staffName' => $session->staff?->name ?? '—',
            'normalRemaining' => $normalRemaining,
            'trialRemaining' => $trialRemaining,
            'totalRemaining' => $totalRemaining,
        ];
    }
}
