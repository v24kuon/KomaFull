<?php

namespace Tests\Feature\Member;

use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Member\MemberWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class MemberSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト観点（抜粋）: ゲストは会員設定へアクセスできない / プロフィールなしは設定ハブからダッシュボードへ /
     * パスワード変更の成功・現在パスワード誤り /
     * メール変更の成功・重複拒否・同一メール拒否・現在パスワード誤り /
     * 退会の成功・管理者/プロフィールなしは 403・確認なし・現在パスワード誤り / 退会済みはマイページ不可・ログイン不可。
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

    public function test_guest_is_redirected_from_member_settings(): void
    {
        $response = $this->get(route('member.settings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_verified_member_can_view_settings_hub(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->get(route('member.settings.index'));

        $response->assertOk();
        $response->assertViewIs('pages.member.settings.index');
        $response->assertSee('会員設定', false);
    }

    /**
     * メール認証後のプロビジョニング失敗などで member_profiles が無い場合、設定ハブで null 参照しないこと。
     */
    public function test_member_without_profile_is_redirected_from_settings_hub(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->get(route('member.settings.index'));

        $response->assertRedirect(route('member.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_member_can_change_password_with_valid_current_password(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->put(route('member.settings.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ]);

        $response->assertRedirect(route('member.settings.password.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-9', (string) $user->password));
    }

    public function test_password_change_fails_when_current_password_is_wrong(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->from(route('member.settings.password.edit'))->put(route('member.settings.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ]);

        $response->assertRedirect(route('member.settings.password.edit'));
        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('password', (string) $user->password));
    }

    public function test_member_can_change_email_and_verification_is_reset(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->put(route('member.settings.email.update'), [
            'current_password' => 'password',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect(route('member.settings.email.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('new-email@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * 現在と同一のメールで送信しても確認メールは送られないため、バリデーションで拒否すること（成功フラッシュの誤表示防止）。
     */
    public function test_email_change_fails_when_email_unchanged(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->from(route('member.settings.email.edit'))->put(route('member.settings.email.update'), [
            'current_password' => 'password',
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('member.settings.email.edit'));
        $response->assertSessionHasErrors('email');
    }

    /**
     * メール変更で未認証に戻った直後も、メール設定 GET は verified により弾かれないこと（成功メッセージ表示・誤入力時の再編集用）。
     *
     * | Case ID | Input / Precondition | Perspective | Expected Result |
     * |---------|----------------------|-------------|-----------------|
     * | TC-N-01 | 変更後 GET email.edit | Equivalence – normal | 200、画面文言が表示される |
     */
    public function test_member_can_view_email_settings_after_email_change_without_reverification(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $this->actingAs($user)->put(route('member.settings.email.update'), [
            'current_password' => 'password',
            'email' => 'new-email@example.com',
        ]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);

        $response = $this->actingAs($user)->get(route('member.settings.email.edit'));

        $response->assertOk();
        $response->assertSee('メールアドレス変更', false);
    }

    /**
     * 初回メール未認証でもプロフィールがあればメール設定へアクセスでき、誤入力の修正に使えること。
     */
    public function test_unverified_member_with_profile_can_access_email_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->get(route('member.settings.email.edit'));

        $response->assertOk();
        $response->assertSee('メールアドレス変更', false);
    }

    public function test_email_change_fails_when_new_email_is_not_unique(): void
    {
        $other = $this->createVerifiedMemberWithProfile();
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->from(route('member.settings.email.edit'))->put(route('member.settings.email.update'), [
            'current_password' => 'password',
            'email' => $other->email,
        ]);

        $response->assertRedirect(route('member.settings.email.edit'));
        $response->assertSessionHasErrors('email');
    }

    public function test_email_change_fails_when_current_password_is_wrong(): void
    {
        $user = $this->createVerifiedMemberWithProfile();
        $originalEmail = $user->email;

        $response = $this->actingAs($user)->from(route('member.settings.email.edit'))->put(route('member.settings.email.update'), [
            'current_password' => 'wrong-password',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect(route('member.settings.email.edit'));
        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertSame($originalEmail, $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_member_can_withdraw_with_valid_password_and_confirmation(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->post(route('member.settings.withdraw.destroy'), [
            'current_password' => 'password',
            'withdrawal_confirmed' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');

        $user->memberProfile?->refresh();
        $this->assertSame(MemberProfile::STATUS_WITHDRAWN, $user->memberProfile?->member_status);
        $this->assertNotNull($user->memberProfile?->withdrawn_at);
    }

    public function test_withdrawal_is_forbidden_for_admin_even_with_member_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->post(route('member.settings.withdraw.destroy'), [
            'current_password' => 'password',
            'withdrawal_confirmed' => '1',
        ]);

        $response->assertForbidden();
        $user->memberProfile?->refresh();
        $this->assertNotSame(MemberProfile::STATUS_WITHDRAWN, $user->memberProfile?->member_status);
    }

    public function test_withdrawal_is_forbidden_when_member_profile_is_missing(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->post(route('member.settings.withdraw.destroy'), [
            'current_password' => 'password',
            'withdrawal_confirmed' => '1',
        ]);

        $response->assertForbidden();
    }

    public function test_withdrawal_fails_without_confirmation(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->from(route('member.settings.withdraw.edit'))->post(route('member.settings.withdraw.destroy'), [
            'current_password' => 'password',
        ]);

        $response->assertRedirect(route('member.settings.withdraw.edit'));
        $response->assertSessionHasErrors('withdrawal_confirmed');

        $user->memberProfile?->refresh();
        $this->assertNotSame(MemberProfile::STATUS_WITHDRAWN, $user->memberProfile?->member_status);
    }

    public function test_withdrawal_fails_when_current_password_is_wrong(): void
    {
        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->from(route('member.settings.withdraw.edit'))->post(route('member.settings.withdraw.destroy'), [
            'current_password' => 'wrong-password',
            'withdrawal_confirmed' => '1',
        ]);

        $response->assertRedirect(route('member.settings.withdraw.edit'));
        $response->assertSessionHasErrors('current_password');

        $user->memberProfile?->refresh();
        $this->assertNotSame(MemberProfile::STATUS_WITHDRAWN, $user->memberProfile?->member_status);
    }

    public function test_withdrawal_redirects_with_error_when_service_throws(): void
    {
        $this->mock(MemberWithdrawalService::class, function ($mock): void {
            $mock->shouldReceive('withdraw')->once()->andThrow(new RuntimeException('withdrawal failed'));
        });

        $user = $this->createVerifiedMemberWithProfile();

        $response = $this->actingAs($user)->from(route('member.settings.withdraw.edit'))->post(route('member.settings.withdraw.destroy'), [
            'current_password' => 'password',
            'withdrawal_confirmed' => '1',
        ]);

        $response->assertRedirect(route('member.settings.withdraw.edit'));
        $response->assertSessionHas('error');

        $this->assertAuthenticated();
        $user->memberProfile?->refresh();
        $this->assertNotSame(MemberProfile::STATUS_WITHDRAWN, $user->memberProfile?->member_status);
    }

    public function test_withdrawn_member_cannot_access_mypage(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        MemberProfile::factory()->for($user)->withdrawn()->createOne();

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_withdrawn_member_cannot_log_in(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email' => 'withdrawn@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        MemberProfile::factory()->for($user)->withdrawn()->createOne();

        $response = $this->post(route('login'), [
            'email' => 'withdrawn@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * JSON ログイン（Accept: application/json）でも退会済みは認証成功にしないこと。
     */
    public function test_withdrawn_member_json_login_returns_403_and_guest(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'email' => 'withdrawn-json@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        MemberProfile::factory()->for($user)->withdrawn()->createOne();

        $response = $this->postJson(route('login'), [
            'email' => 'withdrawn-json@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'このアカウントは退会済みです。']);
        $this->assertGuest();
    }

    public function test_billing_portal_requires_authentication(): void
    {
        $response = $this->post(route('member.settings.billing-portal'));

        $response->assertRedirect(route('login'));
    }
}
