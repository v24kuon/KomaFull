<?php

namespace App\Http\Controllers;

use App\Models\LessonSession;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * 公開の開催枠カレンダー（月単位）と、日別の枠一覧用データを表示する。
     *
     * 前提: `lesson_sessions.status = active` かつ紐づく `programs.status = active` のみ。
     * 空き記号は当日の全枠の残席合計（一般＋体験）から算出する。
     * 更新方針: 読み取り専用。月はクエリ `year` / `month` で指定（未指定は現在月）。
     */
    public function index(Request $request): View
    {
        $validator = Validator::make($request->query(), [
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        if ($validator->fails()) {
            abort(422);
        }

        $year = (int) $request->query('year', Carbon::now()->year);
        $month = (int) $request->query('month', Carbon::now()->month);

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

        $calendarPayload = [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $monthStart->copy()->locale('ja')->translatedFormat('Y年n月'),
            'weekdayLabels' => ['月', '火', '水', '木', '金', '土', '日'],
            'weeks' => $weeks,
            'sessionsByDay' => $sessionsByDay,
            'prevUrl' => route('schedule.index', ['year' => $prev->year, 'month' => $prev->month]),
            'nextUrl' => route('schedule.index', ['year' => $next->year, 'month' => $next->month]),
        ];

        return view('pages.schedule.index', [
            'calendarPayload' => $calendarPayload,
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
