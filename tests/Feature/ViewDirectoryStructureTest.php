<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ViewDirectoryStructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: 公開トップは pages 配下の view を使うこと。
     */
    public function test_home_route_uses_pages_welcome_view(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('pages.welcome');
    }

    /**
     * TC-N-02/03/04: Fortify 公開画面は pages/auth 配下の view を使うこと。
     */
    #[DataProvider('authPageProvider')]
    public function test_auth_routes_use_pages_directory_views(string $uri, string $viewName): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertViewIs($viewName);
    }

    /**
     * TC-N-05: 管理ダッシュボードは pages/admin 配下の view を使うこと。
     */
    public function test_admin_dashboard_uses_pages_directory_view(): void
    {
        $response = $this->actingAs($this->createAdminUser())->get('/admin');

        $response->assertOk();
        $response->assertViewIs('pages.admin.dashboard');
    }

    /**
     * TC-N-06: 管理カテゴリ一覧の通常表示は pages/admin 配下の view を使うこと。
     */
    public function test_admin_categories_index_uses_pages_directory_view(): void
    {
        Category::factory()->createOne();

        $response = $this->actingAs($this->createAdminUser())->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.categories.index');
    }

    /**
     * TC-A-01: HTMX 一覧更新は partials/admin 配下の view のみを返すこと。
     */
    public function test_admin_categories_index_htmx_request_uses_partials_directory_view(): void
    {
        Category::factory()->createOne();

        $response = $this->actingAs($this->createAdminUser())
            ->withHeader('HX-Request', 'true')
            ->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertViewIs('partials.admin.categories.table');
        $response->assertDontSee('<!DOCTYPE html>', false);
    }

    /**
     * TC-N-07: 公開プログラム一覧の通常表示は pages 配下の view を使うこと。
     */
    public function test_programs_index_uses_pages_directory_view(): void
    {
        Program::factory()->createOne(['status' => Program::STATUS_ACTIVE]);

        $response = $this->get(route('programs.index'));

        $response->assertOk();
        $response->assertViewIs('pages.programs.index');
    }

    /**
     * TC-A-02: HTMX 公開プログラム一覧は partials 配下の view のみを返すこと。
     */
    public function test_programs_index_htmx_request_uses_partials_directory_view(): void
    {
        Program::factory()->createOne(['status' => Program::STATUS_ACTIVE]);

        $response = $this->withHeader('HX-Request', 'true')
            ->get(route('programs.index'));

        $response->assertOk();
        $response->assertViewIs('partials.programs.list');
        $response->assertDontSee('<!DOCTYPE html>', false);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function authPageProvider(): array
    {
        return [
            'login' => ['/login', 'pages.auth.login'],
            'register' => ['/register', 'pages.auth.register'],
            'forgot-password' => ['/forgot-password', 'pages.auth.forgot-password'],
        ];
    }

    private function createAdminUser(): User
    {
        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
        ]);

        return $admin;
    }
}
