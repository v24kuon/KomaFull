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
 * | TC-N-01 | ゲスト、クエリなし | Equivalence – normal | 200、pages.schedule.index | 当月 |
 * | TC-N-02 | active プログラムの枠が1件 | Equivalence – payload | calendarPayload.sessionsByDay に該当日の行 | Alpine 描画はブラウザ前提。サーバー payload で検証 |
 * | TC-N-03 | inactive プログラムの枠のみ | Equivalence – filter | 該当日が sessionsByDay に載らない | whereHas(active) |
 * | TC-A-01 | month=13 | Boundary – invalid | 422 | |
 * | TC-A-02 | year=1999 | Boundary – below min | 422 | |
 *
 * 失敗系: バリデーション失敗のみ。正常系3に対し失敗系2で同数未満だが、本画面は GET のみで追加の HTTP 失敗経路がなく、主要エラー経路はクエリ不正に限定される。
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
     * TC-A-01: month が範囲外のとき 422。
     */
    public function test_schedule_index_invalid_month_unprocessable(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 2026,
            'month' => 13,
        ]));

        $response->assertStatus(422);
    }

    /**
     * TC-A-02: year が下限未満のとき 422。
     */
    public function test_schedule_index_invalid_year_unprocessable(): void
    {
        $response = $this->get(route('schedule.index', [
            'year' => 1999,
            'month' => 1,
        ]));

        $response->assertStatus(422);
    }
}
