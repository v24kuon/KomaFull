<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleIndexRequest;
use App\Models\LessonSession;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * 公開の開催枠カレンダー（月単位）と、日別の枠一覧用データを表示する。
     *
     * 前提: `lesson_sessions.status = active` かつ紐づく `programs.status = active` のみ。
     * 空き記号は当日の全枠の残席合計（一般＋体験）から算出する。
     * 更新方針: 読み取り専用。月はクエリ `year` / `month` で指定（未指定は現在月）。
     * 選択日は `selected=Y-m-d`（表示中の月内かつ今日以降のみ有効。過去日は予約不可のため UI ・サーバー双方で拒否）。
     * 前月・次月リンクは `ScheduleIndexRequest` の年範囲外へ跨ぐ場合は `null` とし、ビューで無効表示する。
     * HTMX（`HX-Request`）のときは `partials.schedule.interactive` のみ返し、カレンダー操作でフルリロードしない。
     * 同一 URL がフルページと HTMX 断片の 2 表現を返すため、レスポンスに `Vary: HX-Request` を付与し下流キャッシュの取り違えを防ぐ。
     * 「今日」はリクエスト処理内で `Carbon::now($tz)->startOfDay()` を1回だけ取得し、`$todayYmd`・`buildCalendarWeeks()` の `isToday` で共有する。
     */
    public function index(ScheduleIndexRequest $request): View|Response
    {
        $validated = $request->validated();
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $tz = config('app.timezone');
        $todayStart = Carbon::now($tz)->startOfDay();
        $todayYmd = $todayStart->format('Y-m-d');
        $selectedYmd = $this->normalizeSelectedYmd($request->query('selected'), $year, $month, $todayYmd);

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

        $sessionsByDay = [];
        $totalsByDay = [];

        foreach ($sessions as $session) {
            /** @var Carbon $starts */
            $starts = $session->starts_at->copy()->timezone($tz);
            $ymd = $starts->format('Y-m-d');
            $remainingSeats = $this->remainingSeatsBreakdown($session);
            $totalsByDay[$ymd] = ($totalsByDay[$ymd] ?? 0) + $remainingSeats['totalRemaining'];
            if (! isset($sessionsByDay[$ymd])) {
                $sessionsByDay[$ymd] = [];
            }
            $sessionsByDay[$ymd][] = $this->serializeSessionRow($session, $starts, $remainingSeats);
        }

        $weeks = $this->buildCalendarWeeks($year, $month, $totalsByDay, $tz, $todayStart);

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

        $viewData = [
            'calendarPayload' => $calendarPayload,
            'schedulePrevUrl' => $schedulePrevUrl,
            'scheduleNextUrl' => $scheduleNextUrl,
            'selectedYmd' => $selectedYmd,
            'todayYmd' => $todayYmd,
        ];

        if ($request->header('HX-Request')) {
            return response()
                ->view('partials.schedule.interactive', $viewData)
                ->header('Vary', 'HX-Request');
        }

        return response()
            ->view('pages.schedule.index', $viewData)
            ->header('Vary', 'HX-Request');
    }

    /**
     * クエリ `selected` を表示月内の Y-m-d に正規化する。不正・月外・今日より前は null。
     *
     * 責務: クエリ改ざんや手入力の `selected` を、日別一覧の表示対象として許容する日付のみに絞る。`null` は「日付未選択」扱い（`index()` の `selectedYmd` が null）。
     * 前提: `$year` / `$month` は `ScheduleIndexRequest` で検証済みの表示年月。`$todayYmd` は `index()` 内の `Carbon::now($tz)->startOfDay()` と同一基準の文字列。`$raw` はクエリ `selected` の生値。
     * 更新方針: 受理条件（月内・今日以降・形式）を変えるときは `ScheduleIndexRequest` のクエリ規則や `pages/schedule/index.blade.php` の日付リンク（`selected` 付与）と同じコミットで揃える。暦日の妥当性は Carbon のラウンドトリップで担保する。
     *
     * `Carbon::createFromFormat('Y-m-d', …)` は存在しない日付を例外にせず翌日等へ繰り上げることがあるため、
     * `$d->format('Y-m-d') === $raw` でラウンドトリップ検証し、暦上無効な文字列は null にする。
     *
     * @param  string  $todayYmd  `config('app.timezone')` 上の当日（Y-m-d）
     */
    private function normalizeSelectedYmd(mixed $raw, int $year, int $month, string $todayYmd): ?string
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        try {
            $d = Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($d->format('Y-m-d') !== $raw) {
            return null;
        }

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        if ($d->lt($start) || $d->gt($end)) {
            return null;
        }

        if ($raw < $todayYmd) {
            return null;
        }

        return $raw;
    }

    /**
     * 指定月の週行カレンダーグリッドを組み立て、各セルに日付・当月内フラグ・空き記号・当日フラグを付与する。
     *
     * 前提: `$totalsByDay` のキーは `$timezone` 上の `Y-m-d` で、その日の全枠の残席合計（一般＋体験）が入る（`index()` で `remainingSeatsBreakdown` を合算したもの）。月の前後にまたがる日は `inMonth: false` とし、記号は付けない。週の区切りは月曜始まり（`Carbon::MONDAY`）とし、当月の第 1 週前・最終週後のパディング日を含む。`isToday` は当月セルかつその日が今日のときだけ true（パディング日では false。`ymd` / `day` が null のセルで「今日」扱いにならないようにする）。
     * 更新方針: `Carbon::now()` は `index()` で1回だけ呼び、`$todayStart` を本メソッドへ渡す（`$todayYmd` との日付境界の不整合を避ける）。週始め曜日やグリッドの取り方を変える場合は `pages/schedule/index.blade.php` の表構造と同じコミットで揃える。記号の閾値は `daySymbolForTotal()` に委譲し、ここでは日次合計の参照のみとする。
     *
     * @param  array<string, int>  $totalsByDay
     * @param  Carbon  $todayStart  `index()` で取得した当日 0 時（アプリタイムゾーン）。`normalizeSelectedYmd` の `$todayYmd` と同一の「今日」基準。
     * @return list<list<array{ymd: string|null, day: int|null, inMonth: bool, symbol: string|null, isToday: bool, hasSessions: bool}>>
     */
    private function buildCalendarWeeks(int $year, int $month, array $totalsByDay, string $timezone, Carbon $todayStart): array
    {
        $first = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $last = $first->copy()->endOfMonth();
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $last->copy()->endOfWeek(Carbon::SUNDAY);

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
                    'isToday' => $inMonth && $cursor->equalTo($todayStart),
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
     * 1 開催枠あたりの一般・体験の残席内訳と合計を返す。
     *
     * 前提: `reservation_management` はカウンタキャッシュであり、真実は `reservations` だが本画面は読み取りのみ。`reservationManagement` が未ロードまたは欠損時は予約 0 件として `capacity` / `trial_capacity` から算出する。
     * 更新方針: 一般・体験の定義やロック順序を変える場合は `ReservationService` 等のドメイン方針と整合させ、日次合計記号と `serializeSessionRow` の表示とも同じ helper 経由に保つ。
     *
     * @return array{normalRemaining: int, trialRemaining: int, totalRemaining: int}
     */
    private function remainingSeatsBreakdown(LessonSession $session): array
    {
        $rm = $session->reservationManagement;
        $reservedNormal = $rm?->reserved_count ?? 0;
        $reservedTrial = $rm?->reserved_trial_count ?? 0;
        $normalRemaining = max(0, $session->capacity - $reservedNormal);
        $trialRemaining = max(0, $session->trial_capacity - $reservedTrial);

        return [
            'normalRemaining' => $normalRemaining,
            'trialRemaining' => $trialRemaining,
            'totalRemaining' => $normalRemaining + $trialRemaining,
        ];
    }

    /**
     * 日別一覧に載せる開催枠 1 行分の view 用ペイロードを組み立てる。
     *
     * 前提: `$startsLocal` は `config('app.timezone')` に正規化した枠の開始日時。`$remainingSeats` は `remainingSeatsBreakdown()` の戻りと同一キーであること（再計算しない）。`program` が解決できない場合の `programUrl` は `#`。表示対象の枠は `index()` のクエリで既に active プログラムに限定済み。
     * 更新方針: キー名や表示用フィールドを増減する場合は `resources/views/pages/schedule/index.blade.php` の日別一覧と同じコミットで揃える。残席の意味・算出は `remainingSeatsBreakdown()` を正とし、ここでは受け取った内訳をそのまま載せる。
     *
     * @param  array{normalRemaining: int, trialRemaining: int, totalRemaining: int}  $remainingSeats
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
    private function serializeSessionRow(LessonSession $session, Carbon $startsLocal, array $remainingSeats): array
    {
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
            'normalRemaining' => $remainingSeats['normalRemaining'],
            'trialRemaining' => $remainingSeats['trialRemaining'],
            'totalRemaining' => $remainingSeats['totalRemaining'],
        ];
    }
}
