<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\Program;
use App\Models\ReservationManagement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 公開の開催枠カレンダー（月別・日別データ）
 *
 * テスト観点表:
 *
 * | Case ID | Input / Precondition | Perspective (Equivalence / Boundary) | Expected Result | Notes |
 * |---------|----------------------|----------------------------------------|-----------------|-------|
 * | TC-N-01 | ゲスト、クエリなし | Equivalence – normal | 200、pages.schedule.index、`Vary: HX-Request` | 当月 |
 * | TC-N-02 | active プログラムの枠が1件 | Equivalence – payload | calendarPayload.sessionsByDay に該当日の行 | Blade で日別一覧を描画。payload で検証 |
 * | TC-N-07 | selected=Y-m-d で枠あり | Equivalence – HTML | プログラム名・時刻が本文に含まれる | GET `selected` |
 * | TC-N-08 | selected なし | Equivalence – HTML | 「日付を選択すると一覧が表示されます」 | selectedYmd null |
 * | TC-N-09 | selected が表示月外 | Boundary – ignore | selectedYmd null、案内のみ | 正規化で月外は無効 |
 * | TC-N-10 | selected 日に枠なし | Equivalence – empty | 「この日に表示できる開催枠はありません」 |  |
 * | TC-N-11 | selected が今日より前 | Boundary – past | selectedYmd null | 手動 URL でも拒否 |
 * | TC-A-06 | selected が暦上無効（Carbon オーバーフロー） | Boundary – invalid date | selectedYmd null | 例: 2026-02-30→3/2 繰上でも raw は却下 |
 * | TC-N-12 | 過去日セル | Equivalence – UI | リンクなし・past クラス | TestNow で今日を固定 |
 * | TC-N-13 | HX-Request | Equivalence – HTMX | partials.schedule.interactive、DOCTYPE なし、`Vary: HX-Request` | - |
 * | TC-N-03 | inactive プログラムの枠のみ | Equivalence – filter | 該当日が sessionsByDay に載らない | whereHas(active) |
 * | TC-A-01 | month=13 | Boundary – above max | リダイレクト、month エラー | HTML GET |
 * | TC-A-02 | year=1999 | Boundary – below min | リダイレクト、year エラー | HTML GET |
 * | TC-A-03 | month=13 | Equivalence – error shape | 422、JSON に message / errors | Accept: application/json |
 * | TC-A-04 | month=0 | Boundary – min-1 | リダイレクト、month エラー | HTML GET |
 * | TC-A-05 | year=abc | Boundary – 非整数 | リダイレクト、year エラー | HTML GET |
 * | TC-N-04 | year=2000, month=1 | Boundary – 年下限の前月 | 200、schedulePrevUrl が null | 1999-12 は 422 のため |
 * | TC-N-05 | year=2100, month=12 | Boundary – 年上限の次月 | 200、scheduleNextUrl が null | 2101-01 は 422 のため |
 * | TC-N-06 | 表示月≠今日の日付がパディングに載る | Boundary – isToday と inMonth | 月外セルは isToday=false | Carbon::setTestNow |
 *
 * 失敗系は正常系件数以上（test-strategy.mdc セクション2）。GET のみのため HTTP 失敗経路はクエリバリデーションに限定し、境界値・型不正を追加して網羅する。
 */
class ScheduleSessionCalendarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: ゲストでスケジュールページが 200・pages.schedule.index となる。
     */
    public function test_guest_schedule_index_ok(): void
    {
        $response = $this->get(route('schedule.index'));

        $response->assertOk();
        $response->assertViewIs('pages.schedule.index');
        $response->assertViewHas('calendarPayload');
        $response->assertHeader('Vary', 'HX-Request');
    }

    /**
     * TC-N-02: active プログラムの開催枠は calendarPayload.sessionsByDay に載る。
     */
    public function test_calendar_payload_includes_session_for_active_program(): void
    {
        $tz = config('app.timezone');
        $starts = Carbon::create(2026, 3, 15, 10, 30, 0, $tz);

        $program = Program::factory()->createOne([
            'status' => Program::STATUS_ACTIVE,
            'name' => 'カレンダー表示ヨガ',
        ]);

        $session = LessonSession::factory()->createOne([
            'program_id' => $program->id,
            'starts_at' => $starts,
            'status' => LessonSession::STATUS_ACTIVE,
        ]);

        ReservationManagement::factory()->createOne([
            'lesson_session_id' => $session->id,
            'reserved_count' => 0,
            'reserved_trial_count' => 0,
        ]);

        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 3,
        ]));

        $response->assertOk();
        $response->assertViewHas('schedulePrevUrl', route('schedule.index', ['year' => 2026, 'month' => 2]));
        $response->assertViewHas('scheduleNextUrl', route('schedule.index', ['year' => 2026, 'month' => 4]));
        $response->assertViewHas('calendarPayload', function (array $payload) {
            $day = $payload['sessionsByDay']['2026-03-15'] ?? [];

            return count($day) === 1
                && $day[0]['programName'] === 'カレンダー表示ヨガ'
                && $day[0]['timeLabel'] === '10:30';
        });
    }

    /**
     * TC-N-03: inactive プログラムに紐づく枠は calendarPayload に含めない。
     */
    public function test_calendar_payload_excludes_sessions_for_inactive_program(): void
    {
        $tz = config('app.timezone');
        $starts = Carbon::create(2026, 4, 10, 9, 0, 0, $tz);

        $program = Program::factory()->createOne([
            'status' => Program::STATUS_INACTIVE,
        ]);

        $session = LessonSession::factory()->createOne([
            'program_id' => $program->id,
            'starts_at' => $starts,
            'status' => LessonSession::STATUS_ACTIVE,
        ]);

        ReservationManagement::factory()->createOne([
            'lesson_session_id' => $session->id,
        ]);

        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $response->assertViewHas('calendarPayload', function (array $payload) {
            return ! isset($payload['sessionsByDay']['2026-04-10']);
        });
    }

    /**
     * TC-A-01: month が範囲外のとき HTML ではリダイレクトしセッションにエラー。
     */
    public function test_schedule_index_invalid_month_unprocessable(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 13,
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['month']);
    }

    /**
     * TC-A-02: year が下限未満のとき HTML ではリダイレクトしセッションにエラー。
     */
    public function test_schedule_index_invalid_year_unprocessable(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 1999,
            'month' => 1,
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['year']);
    }

    /**
     * TC-A-03: JSON を期待するリクエストでは 422 の本文に message と errors が含まれる。
     */
    public function test_schedule_index_invalid_query_returns_json_error_body(): void
    {
        $response = $this->getJson(route('schedule.index', [
            'year' => 2026,
            'month' => 13,
        ]));

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        $this->assertIsArray($response->json('errors.month'));
        $this->assertNotEmpty($response->json('errors.month'));
    }

    /**
     * TC-A-04: month が最小値未満（0）のとき HTML ではリダイレクトしセッションにエラー。
     */
    public function test_schedule_index_month_zero_unprocessable(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 0,
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['month']);
    }

    /**
     * TC-A-05: year が整数でないとき HTML ではリダイレクトしセッションにエラー。
     */
    public function test_schedule_index_non_numeric_year_unprocessable(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 'abc',
            'month' => 1,
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['year']);
    }

    /**
     * TC-N-04: 年下限の 1 月では前月ナビ URL を出さない（1999 年は 422）。
     */
    public function test_schedule_nav_prev_url_null_at_minimum_year_january(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2000,
            'month' => 1,
        ]));

        $response->assertOk();
        $response->assertViewHas('schedulePrevUrl', null);
        $response->assertViewHas('scheduleNextUrl', route('schedule.index', [
            'year' => 2000,
            'month' => 2,
        ]));
    }

    /**
     * TC-N-05: 年上限の 12 月では次月ナビ URL を出さない（2101 年は 422）。
     */
    public function test_schedule_nav_next_url_null_at_maximum_year_december(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2100,
            'month' => 12,
        ]));

        $response->assertOk();
        $response->assertViewHas('schedulePrevUrl', route('schedule.index', [
            'year' => 2100,
            'month' => 11,
        ]));
        $response->assertViewHas('scheduleNextUrl', null);
    }

    /**
     * TC-N-06: グリッドのパディング日（隣月）では「今日」フラグを立てない。
     */
    public function test_calendar_payload_padding_cells_never_mark_today_when_outside_display_month(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::create(2026, 3, 31, 12, 0, 0, $tz)->startOfDay());

        try {
            $response = $this->get(route('schedule.index', [
                'year' => 2026,
                'month' => 4,
            ]));

            $response->assertOk();
            $response->assertViewHas('calendarPayload', function (array $payload) {
                foreach ($payload['weeks'] as $week) {
                    foreach ($week as $cell) {
                        if ($cell['inMonth'] === false && $cell['isToday'] === true) {
                            return false;
                        }
                    }
                }

                return true;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * TC-N-07: `selected` で日付を指定したとき HTML にその日のプログラム名・時刻が含まれる。
     */
    public function test_schedule_index_with_selected_renders_day_sessions_in_html(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 12, 0, 0, $tz));

        try {
            $starts = Carbon::create(2026, 3, 15, 10, 30, 0, $tz);

            $program = Program::factory()->createOne([
                'status' => Program::STATUS_ACTIVE,
                'name' => 'HTML表示ヨガ',
            ]);

            $session = LessonSession::factory()->createOne([
                'program_id' => $program->id,
                'starts_at' => $starts,
                'status' => LessonSession::STATUS_ACTIVE,
            ]);

            ReservationManagement::factory()->createOne([
                'lesson_session_id' => $session->id,
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

            $response = $this->get(route('schedule.index', [
                'year' => 2026,
                'month' => 3,
                'selected' => '2026-03-15',
            ]));

            $response->assertOk();
            $response->assertViewHas('selectedYmd', '2026-03-15');
            $response->assertSee('HTML表示ヨガ', false);
            $response->assertSee('10:30', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * TC-N-08: `selected` なしのとき一覧案内のみ（日別テーブルは出さない）。
     */
    public function test_schedule_index_without_selected_shows_choose_date_prompt(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 3,
        ]));

        $response->assertOk();
        $response->assertViewHas('selectedYmd', null);
        $response->assertSee('日付を選択すると一覧が表示されます。', false);
    }

    /**
     * TC-N-09: `selected` が表示中の月外なら無視し selectedYmd は null。
     */
    public function test_schedule_index_selected_outside_display_month_is_ignored(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 4,
            'selected' => '2026-03-15',
        ]));

        $response->assertOk();
        $response->assertViewHas('selectedYmd', null);
        $response->assertSee('日付を選択すると一覧が表示されます。', false);
    }

    /**
     * TC-N-10: `selected` は有効だがその日に枠がないときメッセージを表示する。
     */
    public function test_schedule_index_selected_day_with_no_sessions_shows_empty_message(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::create(2026, 3, 20, 12, 0, 0, $tz));

        try {
            $response = $this->get(route('schedule.index', [
                'year' => 2026,
                'month' => 3,
                'selected' => '2026-03-20',
            ]));

            $response->assertOk();
            $response->assertViewHas('selectedYmd', '2026-03-20');
            $response->assertSee('この日に表示できる開催枠はありません。', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * TC-A-06: `selected` が暦上無効で Carbon が別日に繰り上げる場合は null（例: 2026-02-30）。
     */
    public function test_schedule_index_selected_invalid_calendar_date_overflow_is_rejected(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::create(2026, 2, 15, 12, 0, 0, $tz));

        try {
            $response = $this->get(route('schedule.index', [
                'year' => 2026,
                'month' => 3,
                'selected' => '2026-02-30',
            ]));

            $response->assertOk();
            $response->assertViewHas('selectedYmd', null);
            $response->assertSee('日付を選択すると一覧が表示されます。', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * TC-N-11: `selected` が今日より前のとき正規化で null（クエリ改ざんでも過去日は選べない）。
     */
    public function test_schedule_index_selected_past_date_is_rejected(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::create(2026, 3, 25, 12, 0, 0, $tz));

        try {
            $response = $this->get(route('schedule.index', [
                'year' => 2026,
                'month' => 3,
                'selected' => '2026-03-15',
            ]));

            $response->assertOk();
            $response->assertViewHas('selectedYmd', null);
            $response->assertSee('日付を選択すると一覧が表示されます。', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * TC-N-13: HX-Request ではカレンダー部分テンプレートのみ返し DOCTYPE を含まない。
     */
    public function test_schedule_index_with_htmx_returns_interactive_partial_without_doctype(): void
    {
        $response = $this->withHeader('HX-Request', 'true')
            ->get(route('schedule.index', [
                'year' => 2026,
                'month' => 3,
            ]));

        $response->assertOk();
        $response->assertViewIs('partials.schedule.interactive');
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertHeader('Vary', 'HX-Request');
    }

    /**
     * TC-N-12: 過去日はカレンダー上リンクにならない（selected= クエリを含まない）。
     */
    public function test_schedule_index_past_day_cells_are_not_selectable_links(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::create(2026, 3, 20, 12, 0, 0, $tz));

        try {
            $response = $this->get(route('schedule.index', [
                'year' => 2026,
                'month' => 3,
            ]));

            $response->assertOk();
            $response->assertViewHas('todayYmd', '2026-03-20');
            $response->assertSee('p-schedule__cell--past', false);
            $response->assertDontSee('selected=2026-03-10', false);
        } finally {
            Carbon::setTestNow();
        }
    }
}
