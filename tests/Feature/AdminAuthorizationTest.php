<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: 未ログインユーザーは /admin にアクセスできないこと。
     */
    public function test_guest_is_redirected_to_login_on_admin_route(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /**
     * TC-N-02: role=member は /admin にアクセスできないこと。
     */
    public function test_member_role_is_forbidden_on_admin_route(): void
    {
        /** @var User $member */
        $member = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($member)->get('/admin');

        $response->assertForbidden();
    }

    /**
     * TC-N-03: role=admin は /admin にアクセスできること。
     */
    public function test_admin_role_can_access_admin_route(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Admin Dashboard');
    }

    /**
     * TC-B-01: role='' は /admin で拒否されること。
     */
    public function test_empty_role_is_forbidden_on_admin_route(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => '',
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    /**
     * TC-B-02: role=NULL は /admin で拒否されること。
     */
    public function test_null_role_is_forbidden_on_admin_route(): void
    {
        /** @var User $user */
        $user = User::factory()->makeOne([
            'role' => null,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    /**
     * TC-B-03: 未知ロールは /admin で拒否されること。
     */
    public function test_unknown_role_is_forbidden_on_admin_route(): void
    {
        /** @var User $staff */
        $staff = User::factory()->createOne([
            'role' => 'staff',
        ]);

        $response = $this->actingAs($staff)->get('/admin');

        $response->assertForbidden();
    }
}
