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

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
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

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
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

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
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

    /**
     * 候補件数が上限ちょうどのルールは生成を許可すること。
     */
    public function test_generate_action_allows_rules_at_exact_candidate_limit(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2028-01-01',
            'end_date' => '2028-12-31',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $response->assertSessionHas('success', 'セッション生成を実行しました。（作成: 366件 / スキップ: 0件）');
        $this->assertDatabaseCount('lesson_sessions', 366);
        $this->assertDatabaseCount('reservation_management', 366);
    }

    /**
     * 候補件数が上限を超えるルールは生成を拒否すること。
     */
    public function test_generate_action_rejects_rules_that_exceed_candidate_limit(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $response->assertSessionHas('error', '生成対象が多すぎます。期間を短くして 366 件以内にしてください。');
        $this->assertDatabaseCount('lesson_sessions', 0);
        $this->assertDatabaseCount('reservation_management', 0);
    }

    /**
     * 期間設定が壊れた既存ルールは 500 にせず利用者向けエラーへ変換すること。
     */
    public function test_generate_action_returns_error_for_rule_with_invalid_effective_period(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-20',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        ProgramRepetitionRule::withoutEvents(
            fn (): bool => $rule->update([
                'start_date' => '2026-04-10',
                'end_date' => '2026-04-01',
            ])
        );

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $response->assertSessionHas('error', '繰り返し設定の内容が不正です。設定を見直してください。');
        $this->assertDatabaseCount('lesson_sessions', 0);
        $this->assertDatabaseCount('reservation_management', 0);
    }

    /**
     * ステータスが壊れた既存ルールからはセッション生成しないこと。
     */
    public function test_generate_action_returns_error_for_rule_with_invalid_status(): void
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

        ProgramRepetitionRule::withoutEvents(
            fn (): bool => $rule->update([
                'status' => 'archived',
            ])
        );

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $response->assertSessionHas('error', '繰り返し設定の内容が不正です。設定を見直してください。');
        $this->assertDatabaseCount('lesson_sessions', 0);
        $this->assertDatabaseCount('reservation_management', 0);
    }

    /**
     * 生成後の成功メッセージは繰り返し設定一覧画面で表示されること。
     */
    public function test_generate_action_displays_result_on_rules_index(): void
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
            ->followingRedirects()
            ->post(route('admin.program-repetition-rules.generate', $rule));

        $response->assertOk();
        $response->assertSeeText('繰り返し設定管理');
        $response->assertSeeText('セッション生成を実行しました。（作成: 3件 / スキップ: 0件）');
    }
}
