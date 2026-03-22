<?php

namespace Tests\Feature;

use App\Mail\ContactInquiryMail;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 公開の店舗一覧・詳細、お問い合わせ、特定商取引法表記
 *
 * テスト観点表:
 *
 * | Case ID | Input / Precondition | Perspective (Equivalence / Boundary) | Expected Result | Notes |
 * |---------|----------------------|----------------------------------------|-----------------|-------|
 * | TC-N-01 | active 店舗1件 | Equivalence – stores index | 200、店舗名表示 | - |
 * | TC-N-02 | active + inactive | Equivalence – filter | active のみ | - |
 * | TC-N-03 | active 0件 | Boundary – empty | 200、空メッセージ | - |
 * | TC-N-04 | active の show | Equivalence | 200、詳細表示 | - |
 * | TC-A-01 | inactive の show | Boundary | 404 | - |
 * | TC-A-02 | 存在しない code | Boundary | 404 | - |
 * | TC-N-10 | GET contact | Equivalence | 200、フォーム | - |
 * | TC-N-11 | POST 有効入力 | Equivalence | リダイレクト、Mail 送信 | - |
 * | TC-A-10 | POST 無効（種別不正） | Boundary | セッションエラー | - |
 * | TC-A-11 | POST body 空 | Boundary | セッションエラー | - |
 * | TC-A-12 | POST 同一分に6回目 | Boundary – throttle | 429 | 5回/分/IP |
 * | TC-N-20 | GET legal | Equivalence | 200、表記見出し | - |
 * | TC-N-21 | GET legal・active 店舗あり | Equivalence | 200、住所・電話を表示 | TC-N-20 補足（プレースホルダではない表示） |
 *
 * test-strategy.mdc §2 項2（失敗系は正常系と同数以上を原則）に対し、同§2 項4のとおり「達成が合理的でない場合はビジネスインパクトの高い分岐および主要なエラー経路を網羅し、理由を Notes に明示する」。本クラスは公開 GET が主で、HTTP として意味のある失敗経路は 404・フォームバリデーション・429（throttle）に集約されるため、形式的に失敗テスト件数を増やさない。
 * 集計（表上の TC-N / TC-A）: 正常系 8 件、失敗系 5 件（TC-A-01,02,10,11,12）。項2 を厳密適用すると失敗系が少ないが、項4の例外に該当すると判断する。
 */
class PublicMiscPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_index_lists_active_locations_only(): void
    {
        $active = Location::factory()->createOne([
            'status' => Location::STATUS_ACTIVE,
            'name' => '表店舗',
        ]);
        Location::factory()->createOne([
            'status' => Location::STATUS_INACTIVE,
            'name' => '非表示店舗',
        ]);

        $response = $this->get(route('stores.index'));

        $response->assertOk();
        $response->assertViewIs('pages.stores.index');
        $response->assertSee('表店舗', false);
        $response->assertDontSee('非表示店舗', false);
        $response->assertViewHas('locations', fn ($c) => $c->count() === 1 && $c->first()->is($active));
    }

    public function test_stores_index_shows_empty_state_when_no_active_locations(): void
    {
        Location::factory()->createOne(['status' => Location::STATUS_INACTIVE]);

        $response = $this->get(route('stores.index'));

        $response->assertOk();
        $response->assertSee('現在表示できる店舗はありません。', false);
    }

    public function test_stores_show_displays_active_location(): void
    {
        $location = Location::factory()->createOne([
            'status' => Location::STATUS_ACTIVE,
            'name' => '詳細テスト店',
            'address' => '東京都テスト区1-1',
        ]);

        $response = $this->get(route('stores.show', $location));

        $response->assertOk();
        $response->assertViewIs('pages.stores.show');
        $response->assertSee('詳細テスト店', false);
        $response->assertSee('東京都テスト区1-1', false);
    }

    public function test_stores_show_returns_not_found_for_inactive_location(): void
    {
        $location = Location::factory()->createOne([
            'status' => Location::STATUS_INACTIVE,
        ]);

        $response = $this->get(route('stores.show', $location));

        $response->assertNotFound();
    }

    public function test_stores_show_returns_not_found_for_unknown_code(): void
    {
        $response = $this->get(route('stores.show', ['location' => 'LOC000000']));

        $response->assertNotFound();
    }

    public function test_contact_create_displays_form(): void
    {
        $response = $this->get(route('contact.create'));

        $response->assertOk();
        $response->assertViewIs('pages.contact.create');
        $response->assertSee('お問い合わせ', false);
    }

    public function test_contact_store_sends_mail_and_redirects_with_flash(): void
    {
        Mail::fake();

        $response = $this->from(route('contact.create'))->post(route('contact.store'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'phone' => '03-1234-5678',
            'inquiry_type' => 'reservation',
            'body' => '予約について質問があります。',
        ]);

        $response->assertRedirect(route('contact.create'));
        $response->assertSessionHas('status');

        Mail::assertSent(ContactInquiryMail::class, function (ContactInquiryMail $mail): bool {
            return $mail->name === '山田太郎'
                && $mail->email === 'yamada@example.com'
                && $mail->inquiryType === 'reservation'
                && str_contains($mail->body, '予約について');
        });
    }

    public function test_contact_store_validation_error_for_invalid_inquiry_type(): void
    {
        Mail::fake();

        $response = $this->from(route('contact.create'))->post(route('contact.store'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'inquiry_type' => 'invalid_type',
            'body' => '本文',
        ]);

        $response->assertSessionHasErrors('inquiry_type');
        Mail::assertNothingSent();
    }

    public function test_contact_store_validation_error_when_body_missing(): void
    {
        Mail::fake();

        $response = $this->from(route('contact.create'))->post(route('contact.store'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'inquiry_type' => 'other',
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
        Mail::assertNothingSent();
    }

    /**
     * TC-A-12: 同一キーで 1 分あたり 5 回を超える POST は 429 となる（throttle:5,1）。
     */
    public function test_contact_store_returns_too_many_requests_after_fifth_submission_in_same_minute(): void
    {
        Mail::fake();

        $payload = [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'inquiry_type' => 'reservation',
            'body' => '問い合わせ本文です。',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('contact.create'))->post(route('contact.store'), $payload)->assertRedirect(route('contact.create'));
        }

        $this->from(route('contact.create'))->post(route('contact.store'), $payload)->assertStatus(429);
    }

    public function test_legal_tokushoho_displays_page(): void
    {
        $response = $this->get(route('legal.tokushoho'));

        $response->assertOk();
        $response->assertViewIs('pages.legal.tokushoho');
        $response->assertSee('特定商取引法に基づく表記', false);
        $response->assertSee('事業者名', false);
    }

    /**
     * TC-N-21: active 店舗がいるとき特商法ページに住所・電話が出る（TC-N-20 の補足）。
     */
    public function test_legal_tokushoho_uses_primary_location_when_present(): void
    {
        Location::factory()->createOne([
            'status' => Location::STATUS_ACTIVE,
            'address' => '大阪府デモ市2-2',
            'tel' => '06-0000-0000',
        ]);

        $response = $this->get(route('legal.tokushoho'));

        $response->assertOk();
        $response->assertSee('大阪府デモ市2-2', false);
        $response->assertSee('06-0000-0000', false);
    }
}
