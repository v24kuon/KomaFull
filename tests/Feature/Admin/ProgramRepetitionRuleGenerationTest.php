<?php

namespace Tests\Feature\Admin;

use App\Models\LessonSession;
use App\Models\ProgramRepetitionRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramRepetitionRuleGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => User::ROLE_ADMIN]);
        $this->admin = $admin;
    }

    /**
     * 管理者は繰り返しルールからセッション生成を手動実行できること。
     */
    public function test_admin_can_generate_sessions_from_a_repetition_rule(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success', 'セッション生成を実行しました。（作成: 3件 / スキップ: 0件）');
        $this->assertDatabaseCount('lesson_sessions', 3);
        $this->assertDatabaseCount('reservation_management', 3);
    }

    /**
     * 管理者が同じルールを再実行すると既存候補はスキップ扱いになること。
     */
    public function test_admin_generate_action_reports_skipped_slots_when_re_run(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success', 'セッション生成を実行しました。（作成: 0件 / スキップ: 3件）');
        $this->assertDatabaseCount('lesson_sessions', 3);
        $this->assertDatabaseCount('reservation_management', 3);
    }

    /**
     * 候補が存在しない場合は 0 件の結果で成功すること。
     */
    public function test_admin_generate_action_succeeds_when_no_candidates_exist(): void
    {
        $rule = ProgramRepetitionRule::factory()->weekly(1)->createOne([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success', 'セッション生成を実行しました。（作成: 0件 / スキップ: 0件）');
        $this->assertDatabaseCount('lesson_sessions', 0);
        $this->assertDatabaseCount('reservation_management', 0);
    }

    /**
     * ゲストは生成アクションにアクセスできないこと。
     */
    public function test_guest_cannot_access_generate_action(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne();

        $response = $this->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('lesson_sessions', 0);
    }

    /**
     * 非管理者は生成アクションにアクセスできないこと。
     */
    public function test_non_admin_cannot_access_generate_action(): void
    {
        /** @var User $member */
        $member = User::factory()->createOne(['role' => User::ROLE_MEMBER]);
        $rule = ProgramRepetitionRule::factory()->createOne();

        $response = $this->actingAs($member)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertForbidden();
        $this->assertDatabaseCount('lesson_sessions', 0);
    }

    /**
     * 存在しないルールIDは 404 になること。
     */
    public function test_generate_action_returns_not_found_for_missing_rule(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', ['program_repetition_rule' => 999999]));

        $response->assertNotFound();
    }

    /**
     * 作成されたセッションは対象ルールの時刻・枠設定を引き継ぐこと。
     */
    public function test_generate_action_persists_sessions_with_rule_configuration(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $session = LessonSession::query()->sole();

        $this->assertSame($rule->program_id, $session->program_id);
        $this->assertSame($rule->location_id, $session->location_id);
        $this->assertSame($rule->staff_id, $session->staff_id);
        $this->assertSame('2026-03-01 10:15:30', $session->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame(12, $session->capacity);
        $this->assertSame(2, $session->trial_capacity);
    }
}
