<?php

namespace Tests\Unit\Http\Controllers\Member;

use App\Http\Controllers\Member\SettingsBillingPortalController;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SettingsBillingPortalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * createOrGetStripeCustomer / redirectToBillingPortal が Stripe 系で失敗したとき、500 にせず会員設定へエラーを付けて戻すこと。
     */
    public function test_billing_portal_redirects_with_flash_error_when_stripe_step_throws(): void
    {
        /** @var User $realUser */
        $realUser = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
        ]);
        MemberProfile::factory()->for($realUser)->createOne();

        $user = Mockery::mock($realUser)->makePartial();
        $user->shouldReceive('createOrGetStripeCustomer')->once()->andThrow(new RuntimeException('Stripe API unavailable'));

        $request = Request::create('/mypage/settings/billing-portal', 'POST');
        $request->setUserResolver(static fn () => $user);

        $response = app(SettingsBillingPortalController::class)($request);

        $testResponse = TestResponse::fromBaseResponse($response, $request);
        $testResponse->assertRedirect(route('member.settings.index'));
        $testResponse->assertSessionHas('error');
    }
}
