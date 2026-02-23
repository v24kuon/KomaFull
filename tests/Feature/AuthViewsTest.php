<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthViewsTest extends TestCase
{
    use RefreshDatabase;

    // ========================================================
    // TC-N-01: ログイン画面表示
    // ========================================================

    public function test_login_screen_can_be_rendered(): void
    {
        // Given: ゲストユーザー
        // When: GET /login にアクセス
        $response = $this->get('/login');

        // Then: 200でログインフォームが表示される
        $response->assertStatus(200);
        $response->assertSee('ログイン');
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
    }

    // ========================================================
    // TC-N-02: 会員登録画面表示
    // ========================================================

    public function test_register_screen_can_be_rendered(): void
    {
        // Given: ゲストユーザー
        // When: GET /register にアクセス
        $response = $this->get('/register');

        // Then: 200で登録フォームが表示される
        $response->assertStatus(200);
        $response->assertSee('会員登録');
        $response->assertSee('お名前');
        $response->assertSee('メールアドレス');
    }

    // ========================================================
    // TC-N-03: 正常な会員登録
    // ========================================================

    public function test_new_users_can_register(): void
    {
        // Given: 有効な登録情報
        // When: POST /register を送信
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Then: ユーザーが作成され認証状態になりリダイレクト
        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    // ========================================================
    // TC-N-04: 正常なログイン
    // ========================================================

    public function test_users_can_authenticate_using_login_screen(): void
    {
        // Given: 登録済みユーザー
        $user = User::factory()->create();

        // When: 正しいメールアドレスとパスワードでPOST /login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Then: 認証成功しリダイレクト
        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    // ========================================================
    // TC-N-05: ログアウト
    // ========================================================

    public function test_users_can_logout(): void
    {
        // Given: 認証済みユーザー
        $user = User::factory()->create();

        // When: POST /logout を送信
        $response = $this->actingAs($user)->post('/logout');

        // Then: ゲスト状態になりリダイレクト
        $this->assertGuest();
        $response->assertRedirect('/');
    }

    // ========================================================
    // TC-N-06: 認証済みユーザーのログイン画面アクセス
    // ========================================================

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        // Given: 認証済みユーザー
        $user = User::factory()->create();

        // When: GET /login にアクセス
        $response = $this->actingAs($user)->get('/login');

        // Then: リダイレクトされる（再ログイン不要）
        $response->assertRedirect('/');
    }

    // ========================================================
    // TC-A-01: ログイン - email空
    // ========================================================

    public function test_login_fails_with_empty_email(): void
    {
        // Given: emailが空
        // When: POST /login を送信
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        // Then: バリデーションエラー、ゲストのまま
        $this->assertGuest();
    }

    // ========================================================
    // TC-A-02: ログイン - password空
    // ========================================================

    public function test_login_fails_with_empty_password(): void
    {
        // Given: passwordが空
        $user = User::factory()->create();

        // When: POST /login を送信
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        // Then: バリデーションエラー、ゲストのまま
        $this->assertGuest();
    }

    // ========================================================
    // TC-A-03: ログイン - 不正な認証情報
    // ========================================================

    public function test_login_fails_with_invalid_credentials(): void
    {
        // Given: 登録済みユーザー
        $user = User::factory()->create();

        // When: 間違ったパスワードでPOST /login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Then: 認証失敗、ゲストのまま
        $this->assertGuest();
    }

    // ========================================================
    // TC-A-04: 登録 - name空
    // ========================================================

    public function test_register_fails_with_empty_name(): void
    {
        // Given: nameが空
        // When: POST /register を送信
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Then: バリデーションエラー、ゲストのまま
        $this->assertGuest();
        $response->assertSessionHasErrors('name');
    }

    // ========================================================
    // TC-A-05: 登録 - email空
    // ========================================================

    public function test_register_fails_with_empty_email(): void
    {
        // Given: emailが空
        // When: POST /register を送信
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Then: バリデーションエラー、ゲストのまま
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ========================================================
    // TC-A-06: 登録 - パスワード不一致
    // ========================================================

    public function test_register_fails_with_password_mismatch(): void
    {
        // Given: passwordとpassword_confirmationが不一致
        // When: POST /register を送信
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        // Then: バリデーションエラー、ゲストのまま
        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    // ========================================================
    // TC-A-07: 登録 - 既存メールアドレス
    // ========================================================

    public function test_register_fails_with_duplicate_email(): void
    {
        // Given: 既に登録済みのメールアドレス
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // When: 同じメールアドレスでPOST /register
        $response = $this->post('/register', [
            'name' => '別のユーザー',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Then: バリデーションエラー（unique制約）
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    // ========================================================
    // TC-A-08: 登録 - パスワード短すぎ
    // ========================================================

    public function test_register_fails_with_short_password(): void
    {
        // Given: 短すぎるパスワード（Passwordデフォルト: 8文字未満）
        // When: POST /register を送信
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ]);

        // Then: バリデーションエラー
        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    // ========================================================
    // TC-N-07: パスワード再設定画面表示
    // ========================================================

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        // Given: ゲストユーザー
        // When: GET /forgot-password にアクセス
        $response = $this->get('/forgot-password');

        // Then: 200でフォームが表示される
        $response->assertStatus(200);
        $response->assertSee('パスワード再設定');
    }

    // ========================================================
    // TC-N-08: メール認証画面表示
    // ========================================================

    public function test_verify_email_screen_can_be_rendered(): void
    {
        // Given: メール未認証の認証済みユーザー
        $user = User::factory()->unverified()->create();

        // When: GET /email/verify にアクセス
        $response = $this->actingAs($user)->get('/email/verify');

        // Then: 200でメール認証案内が表示される
        $response->assertStatus(200);
        $response->assertSee('メールアドレスの確認');
    }
}
