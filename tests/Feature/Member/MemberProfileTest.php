<?php

namespace Tests\Feature\Member;

use App\Models\AdditionalItem;
use App\Models\MemberAdditionalItemValue;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-A-01: 未ログインはプロフィール編集へアクセスできないこと。
     */
    public function test_guest_is_redirected_from_member_profile_edit(): void
    {
        $response = $this->get(route('member.profile.edit'));

        $response->assertRedirect(route('login'));
    }

    /**
     * TC-A-02: メール未認証ユーザーはプロフィール編集へアクセスできないこと。
     */
    public function test_unverified_user_is_redirected_from_member_profile_edit(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->get(route('member.profile.edit'));

        $response->assertRedirect(route('verification.notice'));
    }

    /**
     * TC-A-03: 会員プロフィールがない場合はダッシュボードへ誘導されること。
     */
    public function test_user_without_member_profile_is_redirected_from_profile_edit(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($user)->get(route('member.profile.edit'));

        $response->assertRedirect(route('member.dashboard'));
        $response->assertSessionHas('error');
    }

    /**
     * TC-N-01: 認証済み会員はプロフィール編集画面を表示できること。
     */
    public function test_verified_member_can_view_profile_edit(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->get(route('member.profile.edit'));

        $response->assertOk();
        $response->assertViewIs('pages.member.profile.edit');
        $response->assertSee('プロフィール', false);
    }

    /**
     * TC-N-02: 表示名・電話・生年月日を更新できること。
     */
    public function test_member_can_update_core_profile_fields(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
            'name' => '旧名前',
        ]);

        MemberProfile::factory()->for($user)->createOne([
            'tel' => null,
            'birth_date' => null,
        ]);

        $response = $this->actingAs($user)->put(route('member.profile.update'), [
            'name' => '新しい名前',
            'tel' => '09012345678',
            'birth_date' => '1990-05-01',
        ]);

        $response->assertRedirect(route('member.profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('新しい名前', $user->name);

        $user->memberProfile?->refresh();
        $this->assertSame('09012345678', $user->memberProfile?->tel);
        $this->assertSame('1990-05-01', $user->memberProfile?->birth_date?->format('Y-m-d'));
    }

    /**
     * TC-N-03: 追加項目（テキスト）を保存できること。
     */
    public function test_member_can_save_text_additional_item(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        $profile = MemberProfile::factory()->for($user)->createOne();

        $item = AdditionalItem::factory()->createOne([
            'input_type' => 'text',
            'label_name' => '自由記入',
            'digits' => null,
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->put(route('member.profile.update'), [
            'name' => $user->name,
            'additional_items' => [
                $item->id => 'こんにちは',
            ],
        ]);

        $response->assertRedirect(route('member.profile.edit'));

        $this->assertDatabaseHas('member_additional_item_values', [
            'member_profile_id' => $profile->id,
            'additional_item_id' => $item->id,
            'value' => 'こんにちは',
        ]);
    }

    /**
     * TC-N-04: 追加項目（チェックボックス）を保存できること。
     */
    public function test_member_can_save_checkbox_additional_item(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        $profile = MemberProfile::factory()->for($user)->createOne();

        $item = AdditionalItem::factory()->createOne([
            'input_type' => 'checkbox',
            'label_name' => '同意',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->put(route('member.profile.update'), [
            'name' => $user->name,
            'additional_items' => [
                $item->id => '1',
            ],
        ]);

        $response->assertRedirect(route('member.profile.edit'));

        $this->assertDatabaseHas('member_additional_item_values', [
            'member_profile_id' => $profile->id,
            'additional_item_id' => $item->id,
            'value' => '1',
        ]);
    }

    /**
     * TC-N-05: 追加項目（セレクト）で候補内の値のみ保存できること。
     */
    public function test_member_can_save_select_additional_item_within_options(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        $profile = MemberProfile::factory()->for($user)->createOne();

        $item = AdditionalItem::factory()->createOne([
            'input_type' => 'select',
            'label_name' => '都道府県',
            'select_options' => ['東京都', '大阪府'],
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->put(route('member.profile.update'), [
            'name' => $user->name,
            'additional_items' => [
                $item->id => '東京都',
            ],
        ]);

        $response->assertRedirect(route('member.profile.edit'));

        $this->assertDatabaseHas('member_additional_item_values', [
            'member_profile_id' => $profile->id,
            'additional_item_id' => $item->id,
            'value' => '東京都',
        ]);
    }

    /**
     * TC-A-04: お名前が空ならバリデーションエラーとなること。
     */
    public function test_validation_error_when_name_is_empty(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $response = $this->actingAs($user)->from(route('member.profile.edit'))->put(route('member.profile.update'), [
            'name' => '',
        ]);

        $response->assertRedirect(route('member.profile.edit'));
        $response->assertSessionHasErrors('name');
    }

    /**
     * TC-A-05: セレクトで候補外の値はバリデーションエラーとなること。
     */
    public function test_validation_error_when_select_value_not_in_options(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        MemberProfile::factory()->for($user)->createOne();

        $item = AdditionalItem::factory()->createOne([
            'input_type' => 'select',
            'label_name' => '都道府県',
            'select_options' => ['東京都', '大阪府'],
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->from(route('member.profile.edit'))->put(route('member.profile.update'), [
            'name' => $user->name,
            'additional_items' => [
                $item->id => '不正な県',
            ],
        ]);

        $response->assertRedirect(route('member.profile.edit'));
        $response->assertSessionHasErrors('additional_items.'.$item->id);
    }

    /**
     * TC-N-06: テキスト追加項目を空にすると値行が削除されること。
     */
    public function test_clearing_text_additional_item_removes_value_row(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'role' => User::ROLE_MEMBER,
        ]);

        $profile = MemberProfile::factory()->for($user)->createOne();

        $item = AdditionalItem::factory()->createOne([
            'input_type' => 'text',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        MemberAdditionalItemValue::factory()->createOne([
            'member_profile_id' => $profile->id,
            'additional_item_id' => $item->id,
            'value' => 'before',
        ]);

        $response = $this->actingAs($user)->put(route('member.profile.update'), [
            'name' => $user->name,
            'additional_items' => [
                $item->id => '',
            ],
        ]);

        $response->assertRedirect(route('member.profile.edit'));

        $this->assertDatabaseMissing('member_additional_item_values', [
            'member_profile_id' => $profile->id,
            'additional_item_id' => $item->id,
        ]);
    }
}
