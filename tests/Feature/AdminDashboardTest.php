<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: 管理者はダッシュボード画面を閲覧できること。
     */
    public function test_admin_can_view_dashboard_with_heading(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('ダッシュボード');
    }

    /**
     * TC-N-02: ダッシュボードに管理レイアウト（サイドバーナビ）が描画されること。
     */
    public function test_admin_dashboard_renders_sidebar_navigation(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('id="admin-sidebar"', false);
        $response->assertSee('id="admin-main"', false);
    }

    /**
     * TC-N-03: 管理レイアウトにログアウトリンクが存在すること。
     */
    public function test_admin_dashboard_has_logout_link(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('action="'.route('logout').'"', false);
    }
}
