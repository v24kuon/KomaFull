<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01/02: 公開トップは共通レイアウト経由で描画され、ゲスト導線を持つこと。
     * 併せて認証ユーザー向けのログアウト POST フォームは表示されないこと。
     */
    public function test_guest_welcome_page_uses_shared_layout_assets_and_guest_ctas(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(v_asset('assets/vendor/bootstrap/bootstrap.min.css'), false);
        $response->assertSee(v_asset('assets/css/app.css'), false);
        $response->assertSee(v_asset('assets/js/app.js'), false);
        $response->assertSee(route('programs.index'), false);
        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
        $response->assertDontSee('action="'.route('logout').'"', false);
    }

    /**
     * TC-N-03: 管理者は誤った /dashboard ではなく管理ダッシュボード導線を見ること。
     * 併せて認証ユーザー共通のログアウト POST フォームが描画されること。
     */
    public function test_admin_user_sees_admin_dashboard_link_on_welcome_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole(User::ROLE_ADMIN))->get('/');

        $response->assertOk();
        $response->assertSee(route('admin.dashboard'), false);
        $response->assertDontSee('href="/dashboard"', false);
        $response->assertSee('action="'.route('logout').'"', false);
    }

    /**
     * TC-N-04: 一般会員はゲスト導線ではなくログアウト導線を見ること。
     * 併せて管理ダッシュボード導線は表示されないこと。
     */
    public function test_member_user_sees_logout_action_instead_of_guest_auth_links(): void
    {
        $response = $this->actingAs($this->createUserWithRole(User::ROLE_MEMBER))->get('/');

        $response->assertOk();
        $response->assertSee('action="'.route('logout').'"', false);
        $response->assertDontSee(route('login'), false);
        $response->assertDontSee(route('register'), false);
        $response->assertDontSee(route('admin.dashboard'), false);
    }

    private function createUserWithRole(string $role): User
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => $role,
        ]);

        return $user;
    }
}
