<?php

namespace Tests\Feature\Member;

use App\Models\BalanceTransaction;
use App\Models\CourseEntitlement;
use App\Models\CoursePlan;
use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Program;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-A-01: 未ログインはマイページへアクセスできないこと。
     */
    public function test_guest_is_redirected_from_member_dashboard(): void
    {
        $response = $this->get(route('member.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * TC-A-01b: 管理者は会員マイページへアクセスできないこと。
     */
    public function test_administrator_cannot_access_member_dashboard(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('member.dashboard'));

        $response->assertForbidden();
    }

    /**
     * TC-A-02: メール未認証ユーザーはマイページへアクセスできないこと。
     */
    public function test_unverified_user_is_redirected_from_member_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    /**
     * TC-N-01: 認証済み会員はマイページを表示でき、残高カードと予約見出しを含むこと。
     */
    public function test_verified_member_sees_dashboard_summary(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertViewIs('pages.member.dashboard');
        $response->assertSee('回数券', false);
        $response->assertSee('ポイント', false);
        $response->assertSee('これからの予約', false);
    }

    /**
     * TC-N-02: 台帳合計がダッシュボードに反映されること。
     */
    public function test_dashboard_shows_balance_from_ledger(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        BalanceTransaction::factory()->createOne([
            'user_id' => $user->id,
            'unit' => BalanceTransaction::UNIT_TICKETS,
            'amount' => 5,
            'transaction_type' => BalanceTransaction::TYPE_GRANT,
        ]);

        BalanceTransaction::factory()->createOne([
            'user_id' => $user->id,
            'unit' => BalanceTransaction::UNIT_POINTS,
            'amount' => 120,
            'transaction_type' => BalanceTransaction::TYPE_GRANT,
        ]);

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertViewHas('ticketBalance', 5);
        $response->assertViewHas('pointBalance', 120);
    }

    /**
     * TC-N-03: 今後の確定予約が一覧に表示されること。
     */
    public function test_dashboard_lists_upcoming_confirmed_reservation(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $program = Program::factory()->createOne(['name' => 'ダッシュボード表示テストプログラム']);

        $session = LessonSession::factory()->createOne([
            'program_id' => $program->id,
            'starts_at' => now()->addDays(2),
        ]);

        Reservation::factory()->createOne([
            'user_id' => $user->id,
            'lesson_session_id' => $session->id,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertSee('ダッシュボード表示テストプログラム', false);
    }

    /**
     * TC-N-04: 今周期のサブスク枠が表示されること。
     */
    public function test_dashboard_shows_active_subscription_entitlement(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $plan = CoursePlan::factory()->createOne(['name' => '週末プラン']);

        CourseEntitlement::factory()->createOne([
            'user_id' => $user->id,
            'course_plan_id' => $plan->id,
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
            'granted_uses' => 4,
            'used_uses' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertOk();
        $response->assertSee('週末プラン', false);
        $response->assertSee('残り 3', false);
    }
}
