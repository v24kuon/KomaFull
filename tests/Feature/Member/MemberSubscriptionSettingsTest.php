<?php

namespace Tests\Feature\Member;

use App\Models\CoursePlan;
use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Member\MemberSubscriptionManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Laravel\Cashier\Subscription;
use RuntimeException;
use Stripe\Subscription as StripeSubscription;
use Tests\TestCase;

class MemberSubscriptionSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト観点（抜粋）: ゲスト拒否 / 管理者403 / プロフィールなしリダイレクト /
     * 有効サブスクなしメッセージ / プラン名表示 / swap バリデーション・成功（mock）/ cancel・resume 成功（mock）/
     * swap・cancel・resume 失敗時フラッシュエラー（サービス例外 mock）/ cancel・resume の確認チェック必須 /
     * cancel・resume の after バリデーション（解約不可・猶予外）/ swap の canSwap 不可。
     */
    private function createVerifiedMemberWithProfile(): User
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        MemberProfile::factory()->for($user)->createOne();

        return $user;
    }

    public function test_guest_is_redirected_from_subscription_settings(): void
    {
        $response = $this->get(route('member.settings.subscription.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_administrator_cannot_access_subscription_settings(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        MemberProfile::factory()->for($admin)->createOne();

        $response = $this->actingAs($admin)->get(route('member.settings.subscription.edit'));

        $response->assertForbidden();
    }

    public function test_member_without_profile_is_redirected_from_subscription_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->get(route('member.settings.subscription.edit'));

        $response->assertRedirect(route('member.dashboard'));
        $response->assertSessionHas('error', MemberProfile::FLASH_ERROR_MISSING_PROFILE_VERIFIED);
    }

    public function test_member_without_subscription_sees_empty_state_message(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->get(route('member.settings.subscription.edit'));

        $response->assertOk();
        $response->assertViewIs('pages.member.settings.subscription');
        $response->assertSee('有効なサブスクリプションはありません', false);
    }

    public function test_member_with_subscription_sees_plan_name_from_course_plan(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $plan = CoursePlan::factory()->createOne([
            'name' => 'テスト月額プラン',
            'stripe_price_id' => 'price_member_sub_display_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice($plan->stripe_price_id)->create();

        $response = $this->actingAs($user)->get(route('member.settings.subscription.edit'));

        $response->assertOk();
        $response->assertSee('テスト月額プラン', false);
    }

    public function test_member_with_subscription_sees_inactive_plan_name_for_display(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->inactive()->createOne([
            'name' => '旧プラン（販売終了）',
            'stripe_price_id' => 'price_inactive_display_001',
        ]);

        Subscription::factory()->for($user)->withPrice('price_inactive_display_001')->create();

        $response = $this->actingAs($user)->get(route('member.settings.subscription.edit'));

        $response->assertOk();
        $response->assertSee('旧プラン（販売終了）', false);
        $response->assertDontSee('プラン（マスタ未登録の料金）', false);
    }

    public function test_swap_rejects_unknown_stripe_price_id(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_current_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_current_001')->create();

        $response = $this->actingAs($user)->post(route('member.settings.subscription.swap'), [
            'stripe_price_id' => 'price_not_in_database',
        ]);

        $response->assertSessionHasErrorsIn('swap', 'stripe_price_id');
    }

    public function test_swap_rejects_same_price_as_current(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_same_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_same_001')->create();

        $response = $this->actingAs($user)->post(route('member.settings.subscription.swap'), [
            'stripe_price_id' => 'price_same_001',
        ]);

        $response->assertSessionHasErrorsIn('swap', 'stripe_price_id');
    }

    public function test_swap_redirects_with_success_when_service_succeeds(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_from_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_to_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_from_001')->create();

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canSwap')->once()->andReturn(true);
            $mock->shouldReceive('swapToPrice')->once();
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.swap'), [
            'stripe_price_id' => 'price_to_001',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('success');
    }

    public function test_swap_redirects_with_error_when_service_throws(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_from_throw_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_to_throw_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_from_throw_001')->create();

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canSwap')->once()->andReturn(true);
            $mock->shouldReceive('swapToPrice')->once()->andThrow(new RuntimeException('Stripe down'));
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.swap'), [
            'stripe_price_id' => 'price_to_throw_001',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('error');
    }

    public function test_swap_redirects_with_business_message_when_service_throws_invalid_argument(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_from_ia_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_to_ia_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_from_ia_001')->create();

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canSwap')->once()->andReturn(true);
            $mock->shouldReceive('swapToPrice')->once()->andThrow(new InvalidArgumentException('Invalid course plan.'));
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.swap'), [
            'stripe_price_id' => 'price_to_ia_001',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('error', '選択したプランは現在ご利用できません。');
    }

    public function test_swap_fails_validation_when_subscription_cannot_be_swapped(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_swap_blocked_from_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_swap_blocked_to_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_swap_blocked_from_001')->create();

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canSwap')->once()->andReturn(false);
        });

        $response = $this->actingAs($user)
            ->from(route('member.settings.subscription.edit'))
            ->post(route('member.settings.subscription.swap'), [
                'stripe_price_id' => 'price_swap_blocked_to_001',
            ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHasErrorsIn('swap', [
            'stripe_price_id' => '現在、プランを変更できる状態ではありません。',
        ]);
    }

    public function test_cancel_requires_confirmation(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_cancel_val_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_cancel_val_001')->create();

        $response = $this->actingAs($user)->post(route('member.settings.subscription.cancel'), []);

        $response->assertSessionHasErrorsIn('cancel', [
            'cancellation_confirmed' => '解約の内容を確認し、チェックを入れてください。',
        ]);
    }

    public function test_resume_requires_confirmation(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_resume_req_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_resume_req_001')->create([
            'ends_at' => now()->addWeek(),
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('member.settings.subscription.resume'), []);

        $response->assertSessionHasErrorsIn('resume', [
            'resume_confirmed' => '内容を確認し、チェックを入れてください。',
        ]);
    }

    public function test_cancel_redirects_with_success_when_service_succeeds(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_cancel_ok_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_cancel_ok_001')->create();

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canCancelAtPeriodEnd')->once()->andReturn(true);
            $mock->shouldReceive('cancelAtPeriodEnd')->once();
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.cancel'), [
            'cancellation_confirmed' => '1',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('success');
    }

    public function test_cancel_redirects_with_error_when_service_throws(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_cancel_throw_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_cancel_throw_001')->create();

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canCancelAtPeriodEnd')->once()->andReturn(true);
            $mock->shouldReceive('cancelAtPeriodEnd')->once()->andThrow(new RuntimeException('Stripe down'));
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.cancel'), [
            'cancellation_confirmed' => '1',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('error', '解約手続きを完了できませんでした。時間をおいて再度お試しください。');
    }

    public function test_resume_redirects_with_success_when_service_succeeds(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_resume_ok_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_resume_ok_001')->create([
            'ends_at' => now()->addWeek(),
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
        ]);

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canResume')->once()->andReturn(true);
            $mock->shouldReceive('resume')->once();
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.resume'), [
            'resume_confirmed' => '1',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('success');
    }

    public function test_resume_redirects_with_error_when_service_throws(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_resume_throw_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_resume_throw_001')->create([
            'ends_at' => now()->addWeek(),
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
        ]);

        $this->mock(MemberSubscriptionManagementService::class, function ($mock): void {
            $mock->shouldReceive('canResume')->once()->andReturn(true);
            $mock->shouldReceive('resume')->once()->andThrow(new RuntimeException('Stripe down'));
        });

        $response = $this->actingAs($user)->post(route('member.settings.subscription.resume'), [
            'resume_confirmed' => '1',
        ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHas('error', '解約の取り消しを完了できませんでした。時間をおいて再度お試しください。');
    }

    public function test_cancel_fails_validation_when_no_cancelable_subscription(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)
            ->from(route('member.settings.subscription.edit'))
            ->post(route('member.settings.subscription.cancel'), [
                'cancellation_confirmed' => '1',
            ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHasErrorsIn('cancel', 'cancellation_confirmed');
    }

    public function test_resume_fails_validation_when_not_on_grace_period(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        CoursePlan::factory()->createOne([
            'stripe_price_id' => 'price_resume_invalid_001',
            'status' => CoursePlan::STATUS_ACTIVE,
        ]);

        Subscription::factory()->for($user)->withPrice('price_resume_invalid_001')->create([
            'ends_at' => null,
            'stripe_status' => StripeSubscription::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->from(route('member.settings.subscription.edit'))
            ->post(route('member.settings.subscription.resume'), [
                'resume_confirmed' => '1',
            ]);

        $response->assertRedirect(route('member.settings.subscription.edit'));
        $response->assertSessionHasErrorsIn('resume', 'resume_confirmed');
    }
}
