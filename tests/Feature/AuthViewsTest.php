<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthViewsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: ログイン画面表示
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('ログイン');
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
    }

    /**
     * TC-N-02: 会員登録画面表示
     */
    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('会員登録');
        $response->assertSee('お名前');
        $response->assertSee('メールアドレス');
    }

    /**
     * TC-N-03: 正常な会員登録
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(config('fortify.home'));
    }

    /**
     * TC-N-04: 正常なログイン
     */
    public function test_users_can_authenticate_using_login_screen(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    /**
     * TC-N-05: ログアウト
     */
    public function test_users_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * TC-N-06: 認証済みユーザーのログイン画面アクセス
     */
    public function test_authenticated_user_is_redirected_from_login(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/');
    }

    /**
     * TC-N-07: 認証済みユーザーの会員登録画面アクセス
     */
    public function test_authenticated_user_is_redirected_from_register(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->actingAs($user)->get('/register');

        $response->assertRedirect('/');
    }

    /**
     * TC-A-01: ログイン - email空
     */
    public function test_login_fails_with_empty_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * TC-A-02: ログイン - password空
     */
    public function test_login_fails_with_empty_password(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    /**
     * TC-A-03: ログイン - 不正な認証情報
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * TC-A-04: 登録 - name空
     */
    public function test_register_fails_with_empty_name(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('name');
    }

    /**
     * TC-A-05: 登録 - email空
     */
    public function test_register_fails_with_empty_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * TC-A-06: 登録 - パスワード不一致
     */
    public function test_register_fails_with_password_mismatch(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    /**
     * TC-A-07: 登録 - 既存メールアドレス
     */
    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => '別のユーザー',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * TC-A-08: 登録 - パスワード短すぎ
     */
    public function test_register_fails_with_short_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }

    /**
     * TC-N-08: パスワード再設定画面表示
     */
    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('パスワード再設定');
    }

    /**
     * TC-N-09: パスワード再設定フォーム表示
     */
    public function test_reset_password_screen_can_be_rendered(): void
    {
        $token = 'test-reset-token';

        $response = $this->get('/reset-password/'.$token.'?email=test@example.com');

        $response->assertStatus(200);
        $response->assertSee('新しいパスワードの設定');
    }

    /**
     * TC-N-10: メール認証画面表示
     */
    public function test_verify_email_screen_can_be_rendered(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('メールアドレスの確認');
    }

    /**
     * TC-N-11: メール認証リンク再送
     */
    public function test_resend_verification_email_returns_status(): void
    {
        Notification::fake();

        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * TC-N-12: ゲストのメール認証画面アクセス
     */
    public function test_guest_is_redirected_from_verify_email(): void
    {
        $response = $this->get('/email/verify');

        $response->assertRedirect('/login');
    }

    /**
     * TC-N-13: 認証済みユーザーのメール認証画面アクセス
     */
    public function test_verified_user_is_redirected_from_verify_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertRedirect('/');
    }

    /**
     * TC-N-14: メール認証リンククリック
     */
    public function test_unverified_user_can_verify_email_with_valid_signed_link(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/?verified=1');
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
    }
}
