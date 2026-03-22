# Review Feedback Log

目的: レビュー指摘の採用有無と分類結果をコミット単位で必ず記録し、蓄積漏れを防ぐ。

## 記録フォーマット

```text
- date: YYYY-MM-DD
  branch: <branch>
  scope: <対象差分>
  adopted: yes|no
  classification: 汎用|機能固有|PR限定|none
  targets: <更新先ファイル>
  notes: <判定理由・補足>
```

## Entries

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（MemberWithdrawalService の Stripe／DB 境界と残存リスクの PHPDoc 追記）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberWithdrawalService.php
  notes: 指摘は設計理解と運用提案が中心。監視ジョブの実装は必須ではない。cancelNow 成功後の DB 失敗時の一時不整合と再試行収束・運用監視の検討余地をクラス PHPDoc に明示。挙動変更なし。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（SettingsWithdrawalController::destroy を LogoutAndInvalidateSession に統一）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/Member/SettingsWithdrawalController.php, app/Actions/Auth/LogoutAndInvalidateSession.php
  notes: 指摘は有効。退会成功時の logout／invalidate／regenerateToken を共有アクションへ寄せドリフト防止。LogoutAndInvalidateSession の PHPDoc に呼び出し元を追記。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（LogoutAndInvalidateSession::__invoke にエントリーポイント用 PHPDoc）
  adopted: yes
  classification: PR限定
  targets: app/Actions/Auth/LogoutAndInvalidateSession.php
  notes: 指摘は有効。責務・前提・副作用・更新方針を __invoke に記載し、ミドルウェア／LoginResponse との境界を明示。挙動変更なし。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（SettingsEmailController 未認証プロフィールなしを verification.notice へ直接リダイレクト）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/Member/SettingsEmailController.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。member.dashboard は verified のため未認証だと連鎖リダイレクトで error フラッシュが失われる。未認証分岐は verification.notice へ。layouts.app が session error を表示するため案内が見える。Feature テストで文言表示まで確認。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（EnsureMemberNotWithdrawn の PHPDoc を login リダイレクトに明示）
  adopted: yes
  classification: PR限定
  targets: app/Http/Middleware/EnsureMemberNotWithdrawn.php
  notes: 指摘は有効。旧来の「ホームへ」表記は既に修正済みだったが、実装どおり login 名前付きルートとし home ではない旨を PHPDoc に明記。挙動変更なし。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（退会時の logout・session invalidate を LogoutAndInvalidateSession に共通化）
  adopted: yes
  classification: PR限定
  targets: app/Actions/Auth/LogoutAndInvalidateSession.php, app/Http/Middleware/EnsureMemberNotWithdrawn.php, app/Http/Responses/LoginResponse.php
  notes: 指摘は有効。EnsureMemberNotWithdrawn と LoginResponse の同一手順を invokable アクションへ抽出しドリフト防止。レスポンス本文・文言は経路ごとに既存のまま。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（会員マイページに member.role ミドルウェア、UpdateMemberProfileRequest の authorize 強化）
  adopted: yes
  classification: PR限定
  targets: app/Http/Middleware/EnsureMemberRole.php, bootstrap/app.php, routes/web.php, app/Http/Requests/Member/UpdateMemberProfileRequest.php, app/Http/Middleware/EnsureMemberNotWithdrawn.php, tests/Feature/Member/MemberDashboardTest.php, tests/Feature/Member/MemberProfileTest.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。EnsureMemberNotWithdrawn が管理者を素通しするため会員 UI への侵入を member.role で拒否。/mypage 両グループに適用。UpdateMemberProfileRequest を ROLE_MEMBER + memberProfile に統一。メール設定 PHPDoc は既に PHPDoc 化済み（インライン // なし）。Feature テストで管理者 403 を追加。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（verified 配下でプロフィール未作成時の案内を障害・問い合わせ文へ）
  adopted: yes
  classification: PR限定
  targets: app/Models/MemberProfile.php, app/Http/Controllers/Member/SettingsController.php, app/Http/Controllers/Member/SettingsPasswordController.php, app/Http/Controllers/Member/SettingsBillingPortalController.php, app/Http/Controllers/Member/SettingsWithdrawalController.php, app/Http/Controllers/Member/ProfileController.php, app/Http/Controllers/Member/SettingsEmailController.php, resources/views/pages/member/dashboard.blade.php, tests/Feature/Member/MemberSettingsTest.php, tests/Feature/Member/MemberProfileTest.php
  notes: 指摘は有効。verified では「メール認証後に自動作成」を再トリガーできないため、MemberProfile にフラッシュ定数を集約。メール設定（verified 外）は hasVerifiedEmail で未認証案内と分岐。ダッシュボードは alert-warning。Feature テストを追記・強化。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（routes/web.php のメール設定ルート説明を // から PHPDoc へ）
  adopted: yes
  classification: PR限定
  targets: routes/web.php
  notes: 指摘は有効。インラインコメントをルートグループ直前の PHPDoc に移し、リポジトリの PHPDoc 優先方針に合わせた。挙動変更なし。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（請求ポータル Stripe 失敗テストを Feature 化・MemberStripeBillingPortalService 抽出）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberStripeBillingPortalService.php, app/Http/Controllers/Member/SettingsBillingPortalController.php, tests/Feature/Member/MemberSettingsTest.php, tests/Unit/Http/Controllers/Member/SettingsBillingPortalControllerTest.php（削除）
  notes: 指摘は有効。Request::create で controller 直呼びはルート・ミドルウェア・セッションを通らない。Cashier 呼び出しを MemberStripeBillingPortalService に切り出し、Feature で actingAs+post(route)+mock を実施。Unit ファイルは削除。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（LoginResponse の toResponse / redirectPath に PHPDoc 追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Responses/LoginResponse.php
  notes: 指摘は有効。エントリーポイントに責務・副作用・@param、プライベートヘルパーに責務・副作用なしを追記（RFP-009 整合）。挙動変更なし。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（SharedFormUiComponentsTest の名前付きバッグ field-error に role="alert" アサーション追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/SharedFormUiComponentsTest.php
  notes: 指摘は有効。デフォルトバッグの TC-N-02 と揃え、x-ui.field-error が bag 指定時も role="alert" を出すことを明示。コンポーネントは ui/field-error.blade.php で既に role 付与。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（UpdateMemberEmailSettingsRequest / UpdateMemberPasswordSettingsRequest の authorize を ROLE_MEMBER + memberProfile に統一）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/UpdateMemberEmailSettingsRequest.php, app/Http/Requests/Member/UpdateMemberPasswordSettingsRequest.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。EnsureMemberNotWithdrawn は管理者を素通しのため Auth::check のみでは会員専用にならない。WithdrawMemberAccountRequest と同様の認可に変更。管理者・プロフィールなしの PUT は 403 を MemberSettingsTest で追加。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（MemberWithdrawalService の PHPDoc・lockForUpdate・Stripe と DB トランザクション境界）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberWithdrawalService.php
  notes: 指摘のうち「cancelNow をトランザクション内で実行」は現行コードでは既にトランザクション外であり対応不要。クラス・メソッド PHPDoc に lock strategy / transaction boundaries / idempotency を追記。退会確定更新で member_profiles を lockForUpdate して並行時の二重更新を抑止。MemberSettingsTest 通過。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（WithdrawMemberAccountRequest に messages() を追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/WithdrawMemberAccountRequest.php
  notes: 指摘は有効。current_password と withdrawal_confirmed（required/accepted）を日本語で固定。MemberSettingsTest 退会系は通過。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（WithdrawMemberAccountRequest authorize を ROLE_MEMBER + memberProfile に限定）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/WithdrawMemberAccountRequest.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。/mypage はロール未強制のため Auth::check のみでは管理者も通る。admin+プロフィールあり・会員でプロフィールなしは 403 を feature test で担保。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（UpdateMemberPasswordSettingsRequest に messages() を追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/UpdateMemberPasswordSettingsRequest.php
  notes: 指摘は有効。current_password と password（required/string/min/confirmed）に日本語メッセージ。Password::default は min(8) 前提。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（UpdateMemberEmailSettingsRequest に messages() を追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/UpdateMemberEmailSettingsRequest.php
  notes: 指摘は有効。rules の各ルールに対応する日本語メッセージ。同一メールの closure は従来どおり $fail 文言。MemberSettingsTest 全件通過。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（SettingsWithdrawalController destroy で withdraw 例外時は退会画面へ flash error・ログ）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/Member/SettingsWithdrawalController.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。try-catch Throwable、Log::error、logout 前に return。MemberWithdrawalService を mock して例外時は認証維持・非 withdrawn を feature test で確認。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（SettingsController で memberProfile 未作成時は他設定と同様ダッシュボードへ誘導）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/Member/SettingsController.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。Verified 時 CreateProvisionalMemberProfile 失敗は AppServiceProvider で握りつぶされ得る。null チェック＋リダイレクト、未使用の memberProfile ビュー変数を削除。feature test 追加。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（SettingsBillingPortalController の Stripe 例外を捕捉しログ＋会員設定へ flash error）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/Member/SettingsBillingPortalController.php, tests/Unit/Http/Controllers/Member/SettingsBillingPortalControllerTest.php
  notes: 指摘は有効。createOrGetStripeCustomer / redirectToBillingPortal を try-catch（Throwable）、Log::error に user_id と exception。ユニットテストは Mockery で createOrGetStripeCustomer を例外化し TestResponse で redirect と session error を検証。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー依頼（MemberSettingsTest にメール変更・退会の current_password 誤り異常系を追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Member/MemberSettingsTest.php
  notes: 依頼どおり FormRequest の current_password:web 失敗経路を追加。メールは未変更・verified 維持、退会はプロフィール非 withdrawn を検証。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（LoginResponse で wantsJson を退会済み判定より後にし、JSON は 403・logout 後 session invalidate 整合）
  adopted: yes
  classification: PR限定
  targets: app/Http/Responses/LoginResponse.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。wantsJson 先行で退会が素通し。退会分岐を先に統一し JSON は 403 JSON。invalidate/regenerateToken は HTML と同一。postJson で feature test 追加。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（email.blade で Fortify の updateProfileInformation バッグを form-errors / field-error / @error で表示）
  adopted: yes
  classification: PR限定
  targets: resources/views/pages/member/settings/email.blade.php, tests/Feature/SharedFormUiComponentsTest.php
  notes: 指摘は有効。validateWithBag とデフォルトバッグが不一致。既存 x-ui は bag 対応済みのため二重表示で両バッグを網羅。SharedFormUiComponentsTest に名前付きバッグの TC を追加。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（メール未変更時は確認メールを送らない挙動と整合するよう UpdateMemberEmailSettingsRequest で同一メールを拒否）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/UpdateMemberEmailSettingsRequest.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。Fortify は email 不変時は確認メール非送信。成功フラッシュのみ誤解を招くため、email が現在値と一致する場合はバリデーションエラー。RFP-013 観点でフォームとサーバー許容値を一致。コントローラ文言は変更なし（成功経路のみ到達）。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（メール変更後も member.settings.email を verified 外にし、成功表示・誤入力時の再編集を可能に）
  adopted: yes
  classification: PR限定
  targets: routes/web.php, app/Http/Controllers/Member/SettingsEmailController.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。email_verified_at null 直後は verified が GET を verification.notice へ飛ばす。/mypage/settings/email のみ auth+member.not_withdrawn に分離。コントローラにルート意図を PHPDoc。変更後 GET と未認証+プロフィールあり GET を feature test で担保。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（MemberWithdrawalService の cancelNow を DB トランザクション外へ。Stripe 副作用と RFP-009 整合）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberWithdrawalService.php
  notes: 指摘は有効。cancelNow は Stripe API と subscriptions 行更新を含みロールバック不能。プロフィール更新のみ connection->transaction。トランザクション内で refresh し退会済みなら no-op。再試行時は active() が false のため二重 cancel を避ける。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PRレビュー（LoginResponse 退会済み拒否時に Auth::logout 後の session invalidate / CSRF regenerateToken を EnsureMemberNotWithdrawn と整合）
  adopted: yes
  classification: PR限定
  targets: app/Http/Responses/LoginResponse.php, tests/Feature/Member/MemberSettingsTest.php
  notes: 指摘は有効。退会済みログイン拒否でもミドルウェア・退会コントローラと同様にセッション無効化とトークン再生成を実施。退会ログイン失敗テストに assertGuest を追加。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（追加項目 input_type の文字列リテラルを AdditionalItemInputType enum に統一、syncAdditionalItemValue の is_scalar 削除）
  adopted: yes
  classification: PR限定
  targets: app/Enums/AdditionalItemInputType.php, app/Http/Requests/Admin/StoreAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php, app/Http/Requests/Admin/Concerns/PreparesAdditionalItemSelectOptions.php, app/Http/Requests/Member/UpdateMemberProfileRequest.php, app/Services/Member/MemberProfileUpdateService.php, database/factories/AdditionalItemFactory.php, resources/views/pages/member/profile/edit.blade.php, resources/views/partials/admin/additional-items/form.blade.php
  notes: 指摘は有効。PrepaidType と同様の backed enum。Rule::in(values()) でバリデーションと単一情報源を整合。UpdateMemberProfileRequest 検証済みデータ前提で (string) キャストに簡略化し PHPDoc で意図を明示。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（MemberProfileUpdateService の additional_item_type を TYPE_MEMBER_PROFILE に統一、syncAdditionalItemValue に責務 PHPDoc）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberProfileUpdateService.php
  notes: 指摘は有効。Controller/FormRequest と同様に定数参照でタイポ時の静かな空結果を避ける。RFP-009 に沿い private ヘルパーに前提・更新方針・副作用を記載。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（additional_item_type の member_profile を AdditionalItem::TYPE_MEMBER_PROFILE に定数化）
  adopted: yes
  classification: PR限定
  targets: app/Models/AdditionalItem.php, app/Http/Controllers/Member/ProfileController.php, app/Http/Requests/Member/UpdateMemberProfileRequest.php
  notes: 指摘は有効。タイポ時の静かな空結果を避け IDE 補完を効かせる。MemberProfileUpdateService の同一リテラルは本コミット対象外。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（Store/Update AdditionalItemRequest の prepareForValidation を Trait へ抽出）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Admin/Concerns/PreparesAdditionalItemSelectOptions.php, app/Http/Requests/Admin/StoreAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php
  notes: 指摘は有効。重複を PreparesAdditionalItemSelectOptions に集約。トレイトは App\Http\Requests\Admin\Concerns を FQN で use。select 以外時の select_options クリアと空行除去ロジックを維持。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（Alpine additionalItemForm に change リスナーの destroy / removeEventListener）
  adopted: yes
  classification: PR限定
  targets: public/assets/js/app.js, config/app.php, .env.example
  notes: 指摘は有効。input_type 要素とハンドラ参照を保持し destroy で解除。submitState と同様。ASSET_VERSION を 20260322_10 に更新。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（MemberProfileUpdateService の User 更新を forceFill から fill へ）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberProfileUpdateService.php
  notes: 指摘は有効。name は User::$fillable のため fill で十分。マスアサイン保護を維持。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（管理フォーム x-show により非 select 時も select_options_lines が送信される点のサーバー側クリア）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Admin/StoreAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php, tests/Feature/Admin/AdminAdditionalItemCrudTest.php
  notes: 指摘は有効。prepareForValidation で input_type !== select のとき select_options を null に統一。Blade の x-if は未採用（サーバー正で十分）。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（UpdateMemberProfileRequest の activeAdditionalItems が同一リクエストで3回 SELECT する点のメモ化）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Member/UpdateMemberProfileRequest.php
  notes: 指摘は有効。cachedActiveAdditionalItems で1回だけ取得し rules / attributes / prepareForValidation で再利用。挙動は不変。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PRレビュー（追加項目の select_options_lines 正規化で array_filter 無コールバックにより文字列 "0" が欠落する問題）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Admin/StoreAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php, tests/Feature/Admin/AdminAdditionalItemCrudTest.php
  notes: 指摘は有効。PHP の array_filter（コールバックなし）は (bool)string が false な要素を落とすため "0" が消える。空行除去のみ static fn (string $v) => $v !== '' に変更。store/update の Feature テストを追加。

- date: 2026-03-22
  branch: feat/ph10-2-2-profile-ui
  scope: PH10-2-2 会員プロフィール表示・編集（追加項目・セレクト候補）の実装
  adopted: no
  classification: none
  targets: app/Http/Controllers/Member/ProfileController.php, app/Http/Requests/Member/UpdateMemberProfileRequest.php, app/Services/Member/MemberProfileUpdateService.php, app/Models/MemberAdditionalItemValue.php, database/migrations/2026_03_22_075048_add_select_options_to_additional_items_table.php, database/migrations/2026_03_22_075049_create_member_additional_item_values_table.php, tests/Feature/Member/MemberProfileTest.php
  notes: PRレビュー指摘の採用なし。コミット前の判定記録として classification none を付与。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（MemberDashboardSummary::build の PHPDoc に責務・副作用・トランザクション・冪等性を追記）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberDashboardSummary.php
  notes: 指摘は有効。RFP-009 に沿い読み取り専用サービスの契約を明示。副作用なし・トランザクション未使用・冪等性は該当しない旨を記載。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（マイページ dashboard で STATUS_WITHDRAWN を「退会済み」ラベルで明示）
  adopted: yes
  classification: PR限定
  targets: resources/views/pages/member/dashboard.blade.php
  notes: 指摘は有効。退会済みがフォールバックで生値表示されないよう MemberProfile::STATUS_WITHDRAWN を分岐。将来の未知ステータスは従来どおり @else で表示。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（ViewDirectoryStructureTest の member.dashboard テストで email_verified_at を明示）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ViewDirectoryStructureTest.php
  notes: 指摘は有効。verified ミドルウェア前提ルートのため、Factory 既定への暗黙依存を避け email_verified_at を now() で固定。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（member.css のタイポグラフィを app.css デザイントークンへ寄せ、ASSET_VERSION をバンプ）
  adopted: yes
  classification: PR限定
  targets: public/assets/css/app.css, public/assets/css/pages/member.css, config/app.php, .env.example
  notes: 指摘は有効。既存の --app-font-size-sm を利用し、600 用に --app-font-weight-semibold を社長変数ゾーンに追加。public/assets 変更に合わせ no-build 規約どおり asset_version を 20260322_8 へ。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（AuthViewsTest 会員ログインテストで role を ROLE_MEMBER 明示）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/AuthViewsTest.php
  notes: 指摘は有効。Factory や users.role デフォルト変更時も「一般会員ログイン→member.dashboard」意図が揺れないよう createOne に role を固定。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（MemberDashboardSummary の build 内で now() を単一取得に統一）
  adopted: yes
  classification: PR限定
  targets: app/Services/Member/MemberDashboardSummary.php
  notes: 指摘は有効。サブスク期間判定と upcoming 予約の基準時刻を同一インスタンスに固定し、日付境界でのわずかな不整合リスクを排除。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（LoginResponse の intended 優先と管理者デフォルトの関係をテスト・PHPDocで明示）
  adopted: yes
  classification: PR限定
  targets: app/Http/Responses/LoginResponse.php, tests/Feature/AuthViewsTest.php
  notes: 指摘は妥当。redirect()->intended() により url.intended がロール別フォールバックより優先されることは Laravel 仕様どおり。挙動変更はプロダクト判断のため行わず、管理者かつ url.intended=マイページのログインで member.dashboard へ遷移する Feature テストを追加。クラス PHPDoc で意図を明示。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: PRレビュー（public/assets/css/pages/member.css 追加に伴い ASSET_VERSION を 20260322_7 へ更新）
  adopted: yes
  classification: PR限定
  targets: config/app.php, .env.example
  notes: 指摘は有効。no-build 規約どおり v_asset 利用資産変更と同じリリースで asset_version のデフォルトと .env.example をバンプ。リポジトリ内に 20260322_6 の固定期待テストはなし。

- date: 2026-03-22
  branch: feat/ph10-2-1-mypage-dashboard
  scope: [PH10-2-1] マイページ・ダッシュボード（予約状況・残高サマリ）
  adopted: no
  classification: none
  targets: app/Http/Controllers/Member/DashboardController.php, app/Http/Responses/LoginResponse.php, app/Providers/AppServiceProvider.php, app/Services/Member/MemberDashboardSummary.php, app/Models/User.php, routes/web.php, resources/views/layouts/member.blade.php, resources/views/pages/member/dashboard.blade.php, resources/views/pages/welcome.blade.php, public/assets/css/pages/member.css, tests/Feature/Member/MemberDashboardTest.php, tests/Feature/AuthViewsTest.php, tests/Feature/WelcomePageTest.php, tests/Feature/ViewDirectoryStructureTest.php
  notes: プラン To-do 実装。外部レビュー前のため採用指摘なし。Fortify ログイン成功時のリダイレクトを一般会員は member.dashboard、管理者は admin.dashboard に分岐。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（PublicMiscPagesTest の Mail::assertSent で phone を検証）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/PublicMiscPagesTest.php
  notes: 指摘は有効。ContactInquiryMail の phone が送信データと一致することを assertSent コールバックで明示。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（お問い合わせフォームの email に maxlength=255 を追加）
  adopted: yes
  classification: PR限定
  targets: resources/views/pages/contact/create.blade.php
  notes: 指摘は有効。name/phone と同様、サーバー max:255 と整合する maxlength を付与。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（StoreContactRequest::labelFor の unknown を InvalidArgumentException に）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/StoreContactRequest.php, tests/Unit/StoreContactRequestTest.php
  notes: 指摘は有効。validated 経路では到達しないが、将来の誤用で生値を返さないよう default で例外。既知4種は DataProvider で回帰確認。PHPUnit の DataProvider と #[DataProvider] を使用。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（public-misc の max-width を app.css の CSS 変数に寄せる）
  adopted: yes
  classification: PR限定
  targets: public/assets/css/app.css, public/assets/css/pages/public-misc.css, config/app.php, .env.example
  notes: 指摘は一部有効。ページ固有のままでも許容とあったが、既存の社長変数ゾーンに --app-page-content-max-contact / --app-page-content-max-legal を追加し public-misc は var() 参照のみ。640px/800px は 40rem/50rem（16px 基準）に相当。ASSET_VERSION を更新。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（mail.contact_to のフォールバックを config/mail.php で定義）
  adopted: yes
  classification: PR限定
  targets: config/mail.php, app/Http/Controllers/ContactController.php, .env.example
  notes: 指摘は有効。MAIL_CONTACT_TO 未設定時に null になり得た定義を、MAIL_FROM_ADDRESS デフォルトと共有する `$mailFromAddress` へ `?:` で集約。ContactController の二重フォールバックを削除。.env.example のコメントを整合。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（PublicMiscPagesTest の観点表と test-strategy.mdc §2 項4 の整合を PHPDoc で明示）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/PublicMiscPagesTest.php
  notes: 指摘は有効。項2の「失敗系は正常系と同数以上」は項4の例外（合理的でない場合は主要エラー経路＋Notes）で説明可能と判断。表に TC-N-21 と TC-A-12 を反映し、集計行を更新。legal 店舗ありテストに TC-N-21 の PHPDoc を付与。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: PRレビュー（POST /contact に throttle:5,1 を付与しスパム送信を抑制）
  adopted: yes
  classification: PR限定
  targets: routes/web.php, tests/Feature/PublicMiscPagesTest.php
  notes: 指摘は有効。認証不要のメール送信 POST にレート制限がなく悪用可能なため、`throttle:5,1`（Fortify ログインの 5/分 と同水準）を route のみに適用。公開 GET 他ルートは従来どおり。Feature テストで 6 回目が 429 となることを確認。

- date: 2026-03-22
  branch: feat/ph9-2-4-public-pages-misc
  scope: [PH9-2-4] その他公開ページ（Store, Contact, Legal）
  adopted: no
  classification: none
  targets: routes/web.php, app/Http/Controllers/PublicStoreController.php, app/Http/Controllers/ContactController.php, app/Http/Controllers/LegalController.php, app/Http/Requests/StoreContactRequest.php, app/Mail/ContactInquiryMail.php, resources/views/pages/stores/*.blade.php, resources/views/pages/contact/create.blade.php, resources/views/pages/legal/tokushoho.blade.php, resources/views/mail/contact-inquiry.blade.php, resources/views/partials/public/site-footer.blade.php, public/assets/css/pages/public-misc.css, config/mail.php, config/app.php, .env.example, resources/views/pages/welcome.blade.php, tests/Feature/PublicMiscPagesTest.php, tests/Feature/ViewDirectoryStructureTest.php, tests/Feature/WelcomePageTest.php
  notes: 新規実装のため採用レビュー指摘はなし。公開 GET /stores・/stores/{code}（active のみ）、GET/POST /contact（ContactInquiryMail・mail.contact_to）、GET /legal/tokushoho。共通フッターに導線追加。お問い合わせのメール検証は DNS ルックアップを避けるため email:filter を使用。ASSET_VERSION を 20260322_5 へ。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（schedule.css の primary 透明度を .p-schedule にカスタムプロパティ集約）
  adopted: yes
  classification: PR限定
  targets: public/assets/css/pages/schedule.css, config/app.php, .env.example
  notes: 指摘は有効。rgba(var(--bs-primary-rgb), …) の直書きを --p-schedule-primary-fill-08/12 と muted をルートに集約。schedule.css 変更に合わせ ASSET_VERSION を 20260322_4 へ。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ASSET_VERSION の .env 上書き時はリリース単位で同時更新する旨を config / .env.example に明記）
  adopted: yes
  classification: PR限定
  targets: config/app.php, .env.example
  notes: 指摘は有効。`env('ASSET_VERSION', default)` は .env 指定時にデフォルトを使わないため、デプロイ運用をブロックコメントと .env.example コメントで残した。値は既に一致のため数値のみの変更はなし。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleController の daySymbolForTotal / totalRemainingSeats に PHPDoc を追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/ScheduleController.php
  notes: 指摘は有効。責務・前提・更新方針と閾値・凡例との対応を PHPDoc に明示し、serializeSessionRow 等と粒度を揃えた。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleSessionCalendarTest の失敗系を正常系以上に拡充）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ScheduleSessionCalendarTest.php
  notes: 指摘は有効。test-strategy の失敗系件数を満たすため TC-A-04（month=0）、TC-A-05（year=非整数）を追加。PHPDoc の観点表と説明を更新。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（schedule 前月・次月リンクを静的 href にし JS 無効時説明と整合）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/ScheduleController.php, resources/views/pages/schedule/index.blade.php, public/assets/js/app.js, config/app.php, .env.example, tests/Feature/ScheduleSessionCalendarTest.php
  notes: 指摘は有効。x-bind:href だけでは JS 無効時に href が付かない。schedulePrevUrl / scheduleNextUrl を Blade で出力し、Alpine ペイロードから prevUrl・nextUrl を削除。app.js 変更に合わせ ASSET_VERSION を 20260322_3 へ。Feature テストで前後月の route を assertSee。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（.env.example の ASSET_VERSION を config/app.php のデフォルトと整合）
  adopted: yes
  classification: PR限定
  targets: .env.example
  notes: 指摘は有効。`env('ASSET_VERSION')` が .env に入ると config のデフォルトより優先されるため、新規開発者が .env.example をコピーしたときに版数が古いままになる問題を解消。ASSET_VERSION を 20260322_2 に揃えた。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleController のインラインバリデーションを FormRequest へ移行）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/ScheduleIndexRequest.php, app/Http/Controllers/ScheduleController.php, tests/Feature/ScheduleSessionCalendarTest.php
  notes: 指摘は有効。laravel-boost の「FormRequest で検証」に合わせ ScheduleIndexRequest を新設し rules / messages を日本語で定義。GET かつ 422 期待のため failedValidation で JSON 422 を返却。prepareForValidation で年月未指定を現在に補完。TC-A-03 で JSON 形を検証。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: [PH9-2-3] 開催枠カレンダー・日別一覧UI（Alpine.js）
  adopted: no
  classification: none
  targets: app/Http/Controllers/ScheduleController.php, routes/web.php, resources/views/pages/schedule/index.blade.php, public/assets/js/app.js, public/assets/css/pages/schedule.css, resources/views/pages/welcome.blade.php, resources/views/pages/programs/index.blade.php, config/app.php, tests/Feature/ScheduleSessionCalendarTest.php, tests/Feature/ViewDirectoryStructureTest.php, tests/Feature/WelcomePageTest.php
  notes: 新規実装のため採用レビュー指摘はなし。公開 GET /schedule で月別グリッド（月〜日）と空き記号を表示し、日別一覧は Alpine `sessionCalendar` で選択日のみ表示。残席は一般・体験を分けて表示。`programs.status=active` の枠のみ。クエリ year/month は 422。ASSET_VERSION を更新。

- date: 2026-03-22
  branch: feat/ph9-2-2-program-pages
  scope: PRレビュー（ProgramPublicPageTest の各テストメソッドへ PHPDoc／Case ID 追記）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramPublicPageTest.php
  notes: 指摘は有効。test-strategy の「テストケース ID はメソッド直上 PHPDoc」と ViewDirectoryStructureTest の TC 形式に合わせ、8 メソッドすべてに TC-N/TC-A と一行シナリオを付与。AdminProgramCrudTest 等の広範な統一は本コミット範囲外とした。

- date: 2026-03-22
  branch: feat/ph9-2-2-program-pages
  scope: PRレビュー（ProgramPublicPageTest の失敗系件数と test-strategy の整合）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramPublicPageTest.php
  notes: 指摘は一部有効。読み取り専用 GET のため意味のある HTTP 失敗経路は inactive/存在しない code の 404 のみと判断。観点表に Notes 列と test-strategy の例外（合理的でない場合の主要エラー経路優先・Notes 明示）を PHPDoc に追記。冗長な失敗テストの追加は見送り。

- date: 2026-03-22
  branch: feat/ph9-2-2-program-pages
  scope: PRレビュー指摘対応（Program のルートキー・admin の HTMX 読み込み順・join/with の PHPDoc・review-fix コマンド更新）
  adopted: yes
  classification: PR限定
  targets: app/Models/Program.php, app/Http/Controllers/ProgramController.php, resources/views/layouts/admin.blade.php, .cursor/commands/review-fix.md
  notes: 指摘は有効。`Program::getRouteKeyName()` で `code` をルートキーに統一。管理レイアウトの HTMX を `layouts.app` と同順（app.js→Alpine→Bootstrap→HTMX）に揃え初期化順の差を解消。`publicProgramsQuery` の join と `with` の役割を PHPDoc の「設計判断」で明示。`.cursor/commands/review-fix.md` は対応要否の検証をフェーズ化し Task 利用条件を追記。

- date: 2026-03-22
  branch: feat/ph9-2-2-program-pages
  scope: PRレビュー指摘対応（公開プログラムの PHPDoc・テスト・ホームの named route）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/ProgramController.php, routes/web.php, resources/views/pages/programs/index.blade.php, resources/views/pages/programs/show.blade.php, tests/Feature/ProgramPublicPageTest.php
  notes: 指摘は有効。`publicProgramsQuery()` に責務・前提・更新方針を追記。空一覧（active 0 件）の表示と `@forelse` 空メッセージをテスト追加。存在しない code の GET を `route('programs.show', ['program' => …])` に統一。`/` に `home` を付与し、パンくずのホームを `route('home')` に統一してルート変更に追従しやすくした。

- date: 2026-03-22
  branch: feat/ph9-2-2-program-pages
  scope: [PH9-2-2] プログラム一覧・詳細画面（HTMX）
  adopted: no
  classification: none
  targets: app/Http/Controllers/ProgramController.php, routes/web.php, resources/views/pages/programs/, resources/views/partials/programs/, resources/views/layouts/app.blade.php, resources/views/pages/welcome.blade.php, public/assets/css/pages/programs.css, config/app.php, .env.example, tests/Feature/ProgramPublicPageTest.php, tests/Feature/ViewDirectoryStructureTest.php, tests/Feature/WelcomePageTest.php
  notes: 新規実装のため採用レビュー指摘はなし。公開一覧は status=active のみ、カテゴリ sort_order・名称で並べ替え。詳細は code 解決、inactive は 404。HX-Request 時は一覧・詳細とも partials のみ返却し一覧モーダル埋め込みに利用。公開レイアウトに HTMX を追加し `ASSET_VERSION` を更新した。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（layouts.app の body に HTMX 用 hx-headers を付与）
  adopted: yes
  classification: PR限定
  targets: resources/views/layouts/app.blade.php
  notes: 指摘は有効。`no-build-convention.mdc` 2.2 に従い、公開レイアウトも `layouts/admin` と同様に `body` に `hx-headers` で `X-CSRF-TOKEN` を付与し、将来の公開側 HTMX 利用時の CSRF 前提を揃えた。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（layouts.app の app.name フォールバックを KomaFull に統一）
  adopted: yes
  classification: PR限定
  targets: resources/views/layouts/app.blade.php
  notes: 指摘は有効。`APP_NAME` 未設定時に `layouts/app` のデフォルトタイトルだけが Laravel 表記になる不整合を解消し、`welcome`・`layouts/admin` と同じ `config('app.name', 'KomaFull')` に揃えた。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（WelcomePageTest でゲストのログアウトフォーム不在をアサート）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/WelcomePageTest.php
  notes: 指摘は有効。ゲスト向け TC-N-01/02 で `route('logout')` の POST フォームが描画されないことを明示し、認証済み向け `@else` 分岐との対称性と回帰保護を追加した。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（WelcomePageTest で一般会員の admin.dashboard 非表示をアサート）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/WelcomePageTest.php
  notes: 指摘は有効。管理者テストと対称に、一般会員向け TC-N-04 で `route('admin.dashboard')` が描画されないことを明示し、`@can('access-admin')` 分岐の回帰を補強した。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（WelcomePageTest で管理者のログアウト導線をアサート）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/WelcomePageTest.php
  notes: 指摘は有効。管理者向けテストが admin.dashboard と旧 /dashboard 不在のみだったため、`@else` ブロック共通の `route('logout')` POST フォーム表示を会員テストと同形式で検証し回帰保護を追加した。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（welcome の login/register を Route::has でガード）
  adopted: yes
  classification: PR限定
  targets: resources/views/pages/welcome.blade.php
  notes: 指摘は有効。Fortify で registration を外す等でルートが消えた場合に `route()` が例外となるのを防ぐため、旧 welcome と同様に `Route::has('register')` / `Route::has('login')` でリンクを条件表示に戻した（`pages/auth/login.blade.php` の register リンクと整合）。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（app.css 変更に伴う ASSET_VERSION の更新）
  adopted: yes
  classification: PR限定
  targets: config/app.php, .env.example
  notes: 指摘は有効。`--app-brand-rgb` を app.css に追加したため、同一 `?v=` のままだとブラウザが古い app.css を保持した場合に `welcome.css` の `rgba(var(--app-brand-rgb), …)` が未定義となり得る。no-build 規約に従い `ASSET_VERSION` を 20260319_2 へ更新し、`v_asset()` のキャッシュバスティングを揃えた。

- date: 2026-03-20
  branch: feat/ph9-2-1-welcome-replace
  scope: PRレビュー指摘対応（welcome.css のブランド色重複を CSS 変数へ統一）
  adopted: yes
  classification: PR限定
  targets: public/assets/css/app.css, public/assets/css/pages/welcome.css
  notes: 指摘は有効。welcome.css で `rgba(13, 110, 253, ...)` が 3 箇所重複していたため、社長変数ゾーンへ `--app-brand-rgb` を追加し、ページ CSS は `rgba(var(--app-brand-rgb), ...)` 参照へ統一した。見た目の変更はなく、トークン管理の一貫性だけを改善した。

- date: 2026-03-19
  branch: feat/ph9-2-1-welcome-replace
  scope: [PH9-2-1] welcome.blade.php の置換と共通レイアウト適用
  adopted: no
  classification: none
  targets: resources/views/pages/welcome.blade.php, public/assets/css/pages/welcome.css, tests/Feature/WelcomePageTest.php
  notes: 新規実装のため採用レビュー指摘はなし。welcome を layouts.app に載せ替え、認証状態ごとの導線（ゲスト: login/register、管理者: admin.dashboard、一般会員: logout）を分岐。welcome.css を追加し WelcomePageTest を新設。

- date: 2026-03-19
  branch: feat/ph8-4-1-common-ui-components
  scope: PRレビュー指摘対応（submitState の pageshow リスナーを destroy で解除）
  adopted: yes
  classification: PR限定
  targets: public/assets/js/app.js, tests/Feature/AppLayoutAlpineCspTest.php
  notes: 指摘は有効。submitState は init() で window の `pageshow` リスナーを追加するが、destroy() がなく HTMX で破棄された行フォームのハンドラ参照が残り得た。インスタンスごとに handler を保持し、destroy() で removeEventListener して null へ戻す最小差分で解消した。

- date: 2026-03-19
  branch: feat/ph8-4-1-common-ui-components
  scope: PRレビュー指摘対応（submit-button の display class 競合を解消）
  adopted: yes
  classification: PR限定
  targets: resources/views/components/ui/submit-button.blade.php, tests/Feature/SharedFormUiComponentsTest.php
  notes: 指摘は有効。loading 表示 span が静的 `d-none` と string 形式の `x-bind:class` を併用しており、送信中に `d-none` と `d-inline-flex` が共存し得た。初期非表示は維持したまま、Alpine が `d-none` を外せる object syntax へ変更し、回帰テストを追加した。

- date: 2026-03-19
  branch: feat/ph8-4-1-common-ui-components
  scope: PRレビュー指摘対応（SharedFormUiComponentsTest の到達不能 return を削除）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/SharedFormUiComponentsTest.php
  notes: 指摘は有効。`renderBlade()` の catch 節で呼ぶ `$this->fail()` は例外を投げて処理を中断するため、直後の `return ''` は到達不能だった。挙動変更なしでデッドコードのみ削除した。

- date: 2026-03-19
  branch: feat/ph8-4-1-common-ui-components
  scope: PRレビュー指摘対応（submitState の bfcache 復元時リセットを追加）
  adopted: yes
  classification: PR限定
  targets: public/assets/js/app.js, tests/Feature/AppLayoutAlpineCspTest.php
  notes: 指摘は有効。submitState は startSubmitting() で `submitting` を true にするだけで戻し処理がなく、bfcache 復元時に disabled 状態が残る可能性があった。`pageshow` + `event.persisted` で `submitting=false` へ戻す処理を追加し、ソースレベルの回帰テストを加えた。実ブラウザでの bfcache 再現までは feature test では扱えないため、今回は hook の存在を検証対象とした。

- date: 2026-03-19
  branch: feat/ph8-4-1-common-ui-components
  scope: [PH8-4-1] 共通UI部品（送信ボタンLoading, バリデーションエラー表示）の実装
  adopted: no
  classification: none
  targets: resources/views/components/ui/, public/assets/js/app.js, resources/views/partials/admin/errors.blade.php, resources/views/partials/admin/*/form.blade.php, resources/views/pages/auth/*.blade.php, resources/views/pages/admin/**/create.blade.php, resources/views/pages/admin/**/edit.blade.php, config/app.php, .env.example, tests/Feature/SharedFormUiComponentsTest.php, tests/Feature/AppLayoutAlpineCspTest.php, tests/Feature/AuthViewsTest.php, tests/Feature/Admin/AdminCategoryCrudTest.php
  notes: 新規実装のため採用レビュー指摘はなし。x-ui.submit-button / field-error / form-errors を追加し、app.js に submitState() を登録。auth/admin フォームへ送信 loading と共通エラー表示を適用。ASSET_VERSION を 20260319_1 に更新。

- date: 2026-03-19
  branch: feat/ph8-3-1-alpine-data-standard
  scope: PRレビュー指摘対応（containsInlineAlpineObjectLiteral の属性境界誤検知を修正）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/AppLayoutAlpineCspTest.php
  notes: 指摘は有効。x-data の値を切り出す前に全体へ正規表現を当てていたため、後続属性（例: data-config="{ open: false }"）の {...} を誤検知し得た。extractXDataAttributeValues() で x-data 属性値のみを抽出し、その値内だけを判定するよう修正。あわせて誤検知防止の回帰テストを追加した。

- date: 2026-03-19
  branch: feat/ph8-3-1-alpine-data-standard
  scope: [PH8-3-1] Alpine.data() 登録方式の標準化（x-data直書き禁止規約）
  adopted: no
  classification: none
  targets: public/assets/js/app.js, resources/views/layouts/app.blade.php, resources/views/layouts/admin.blade.php, tests/Feature/AppLayoutAlpineCspTest.php
  notes: 新規実装のため採用レビュー指摘はなし。`app.js` に `alpine:init` フックを追加し、共通の `Alpine.data()` 登録ポイントを固定した。あわせて app/admin レイアウトで `app.js` を Alpine CSP バンドルより前に読み込む順序へ統一し、Blade 全体で `x-data` のインラインオブジェクトリテラルを禁止する回帰テストを追加した。

- date: 2026-03-18
  branch: feat/ph8-2-2-view-structure
  scope: PRレビュー指摘対応（Fortify のビュークロージャへ View 戻り値型を追加）
  adopted: yes
  classification: PR限定
  targets: app/Providers/FortifyServiceProvider.php
  notes: 指摘は有効。FortifyServiceProvider の `loginView` / `registerView` / `verifyEmailView` / `requestPasswordResetLinkView` / `resetPasswordView` はいずれも Blade view を返すコールバックだが、戻り値型が未明示だった。既存コードで使用している `Illuminate\View\View` に合わせて 5件すべてを `: View` へ統一し、`resetPasswordView` だけを先に型付けしていた状態の粒度差も解消した。

- date: 2026-03-18
  branch: feat/ph8-2-2-view-structure
  scope: PRレビュー指摘対応（Fortify resetPasswordView の Request 型宣言を追加）
  adopted: yes
  classification: PR限定
  targets: app/Providers/FortifyServiceProvider.php
  notes: 指摘は有効。Fortify の view callback 群のうち resetPasswordView だけが `$request` 無型で、同一ファイル内でも `RateLimiter::for('login', function (Request $request) { ... })` と粒度が揃っていなかった。既存 import 済みの `Illuminate\Http\Request` をそのまま用いてクロージャ引数に型宣言を追加し、挙動を変えずに静的保証と一貫性を補強した。

- date: 2026-03-17
  branch: feat/ph8-2-2-view-structure
  scope: PRレビュー指摘対応（CategoryController の FK制約違反削除ハンドリングを追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/Admin/CategoryController.php, resources/views/partials/admin/categories/delete_error_row.blade.php, tests/Feature/Admin/AdminCategoryCrudTest.php
  notes: 指摘は有効。CategoryController の destroy() だけが sibling CRUD と異なり QueryException を未捕捉で、Program がぶら下がるカテゴリ削除時に 500 へ落ちていた。ProgramTypeController などと同じ try-catch + isForeignKeyConstraintViolation() + respondDeleteConstraintViolation() パターンへ揃え、通常削除では error フラッシュ、HTMX 削除では category-row を維持したエラー行 HTML を返すよう修正した。あわせて Program を関連データに使った通常/HTMX の失敗系 feature test を追加した。

- date: 2026-03-17
  branch: feat/ph8-2-2-view-structure
  scope: [PH8-2-2] ビューディレクトリ構成固定（pages, partials, components）
  adopted: no
  classification: none
  targets: app/Http/Controllers/Admin/*.php, app/Providers/FortifyServiceProvider.php, routes/web.php, resources/views/pages/, resources/views/partials/, resources/views/components/.gitkeep, tests/Feature/ViewDirectoryStructureTest.php
  notes: 新規実装/構造整理のため採用レビュー指摘はなし。Blade の canonical 配置を `pages` / `partials` / `components` に統一し、Fortify・公開/管理ルート・HTMX 一覧応答の参照先を新構成へ揃えた。回帰として view 名固定テストを追加し、関連 auth/admin feature test を再実行している。

- date: 2026-03-16
  branch: feat/ph6-2-verification
  scope: PR指摘対応（newSessions 取得をルールスコープへ限定）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php
  notes: 指摘は有効。`whereKeyNot($existingSession->id)` のみだと、このテストに将来別ルール由来の `lesson_sessions` 前提データが追加された場合でも混入を拾い得る。新規生成分の確認対象を `program_id` / `location_id` / `staff_id` で対象ルールに限定し、重複skip後に作られた当該ルールのセッションだけを検証するように絞り込んだ。

- date: 2026-03-16
  branch: feat/ph6-2-verification
  scope: PH6-2-6 不足テスト補完（admin経由の週次生成・既存不変・reservation_management 初期化）
  adopted: no
  classification: none
  targets: tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php
  notes: PH6-2-6 最終検証として admin 経由の回帰テスト2件を追加。採用指摘なし。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（weekly の day_of_week 非数値文字列をモデル側で拒否）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。weekly 分岐は raw の `''` だけを必須チェックした後、cast 後の `$this->day_of_week` を 0-6 判定に使っていたため、`abc` や空白文字列はモデル層で 0 として扱われてしまい、非数値入力を早期拒否できていなかった。raw 値に対して空白・整数形式を先に検証し、非数値文字列を InvalidArgumentException で拒否するよう補強した。あわせて `abc`・空白文字列の失敗系と、数値文字列 `0` の正常系を feature test に追加した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（旧 MySQL での CHECK 制約 migration を fail fast 化）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php, tests/Unit/ProgramRepetitionRuleFoundationMigrationTest.php
  notes: 指摘は一部有効。現行 migration は non-sqlite で `ALTER TABLE ... ADD CONSTRAINT ... CHECK` を無条件に実行しており、modern MySQL 相当の CHECK 制約サポートを暗黙前提にしていた。一方で MySQL 8.0.16 未満が必ず構文エラーで migration failure になるという指摘文は強すぎ、公式資料では旧版 MySQL の CHECK 制約定義は parse されても無視されるとされている。DB変更を半端に進めないよう、`up()` 冒頭で MySQL バージョンを確認し、8.0.16 未満では明示例外で fail fast するよう補強し、unit test を追加した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（weekly の day_of_week 空文字をモデル側で先に拒否）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は一部有効。`day_of_week=''` は accessor 参照時に integer cast で 0 として読めるため、weekly のモデル検証は空文字を必須チェックで弾けていなかった。一方で今回の SQLite 実行では保存時の raw 値は空文字のままで、最終的には日曜として保存成功するのではなく DB の CHECK 制約で QueryException になっていた。weekly 分岐で raw attribute の空文字を先に検知して InvalidArgumentException を投げるよう補強し、回帰テストを追加した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PR指摘対応（採用済み review-feedback entry の分類を PR限定へ補正）
  adopted: yes
  classification: PR限定
  targets: .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`classification: none` 自体は許容値だが、`adopted: yes` の entry に使うと蓄積・再利用対象から外れて意味が弱くなる。`addScheduleCheckConstraint` の採用済み entry はこのPR固有の是正内容だったため、分類を PR限定 へ補正した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: cycle_type の許容値外入力をモデル側でも拒否（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。従来の ensureSupportedScheduleConfiguration() は daily の day_of_week 条件だけを確認した後、weekly 以外を早期 return していたため、monthly・空文字・null の cycle_type はモデル層を素通りして DB 制約へ依存していた。cycle_type の strict な許容値チェックを先頭に追加し、通常保存では InvalidArgumentException、withoutEvents() では引き続き QueryException になることを feature test で確認した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: ProgramRepetitionRuleFoundationTest の作成ヘルパーを Factory ベースへ統一（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。query()->create() 直書きでは ProgramRepetitionRuleFactory の生成経路から外れており、将来の defaults や state 拡張との整合が弱かった。テスト本文は変えず、createRule() / createRuleWithoutEvents() の内部だけを factory()->createOne() ベースに置き換えて Factory 経由へ統一した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: DROP 制約処理の対応ドライバー範囲を PHPDoc で明文化（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php
  notes: 指摘は有効。DROP 構文の分岐自体は正しかったが、`sqlite` はテーブル再構築、`mysql` は `DROP CHECK`、`pgsql` / `sqlsrv` は `DROP CONSTRAINT` を使う前提がメソッド宣言だけでは伝わりにくかった。挙動は変更せず、対応ドライバー範囲と `sqlite` の扱いを PHPDoc で明文化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: booted() のライフサイクル責務を PHPDoc で明文化（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php
  notes: 指摘は有効。booted() は saving フックを登録して永続化前にスケジュール制約を検証するエントリーポイントだが、メソッド宣言だけでは責務が伝わりにくかった。挙動は変更せず、ライフサイクルフックの目的を PHPDoc で簡潔に明文化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: end_date 必須をモデル側でも検証して DB 依存を解消（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php
  notes: 指摘は有効。end_date 必須は migration と業務要件で担保されていたが、モデルの saving 検証では未確認だったため、通常保存時は QueryException まで到達していた。ensureSupportedScheduleConfiguration() に end_date の null ガードを追加し、通常保存は InvalidArgumentException、withoutEvents() は引き続き QueryException になることを feature test で確認した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: ensureSupportedScheduleConfiguration の責務を PHPDoc で明文化（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: app/Models/ProgramRepetitionRule.php
  notes: 指摘は有効。検証ロジック自体は妥当だったが、private helper の責務と PH6-2-1 制約を PHPDoc で明示すると保守性が上がる。挙動は変更せず、week_of_month 非対応・daily の day_of_week 禁止・weekly の day_of_week 必須かつ 0-6 制約をメソッド直上に文書化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: ProgramRepetitionRuleModelTest の到達不能ガード削除（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: tests/Unit/ProgramRepetitionRuleModelTest.php
  notes: 指摘は有効。assertTrue(method_exists(...)) の直後に同一条件の否定分岐を置いており、失敗時はそこでテストが終了するため return 分岐は到達不能だった。Program / Location / Staff の3テストから冗長なガードを削除し、意図を保ったままテストを簡潔化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: addScheduleCheckConstraint 内の重複コード統合（PR指摘対応）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php
  notes: 指摘は有効。MySQL / pgsql / sqlsrv の ADD CONSTRAINT CHECK 構文が同一のため、条件分岐を統合し重複を削除した。addScheduleCheckConstraint は up() で sqlite 以外のときのみ呼ばれるため、ドライバーチェック自体も不要と判断し単一の DB::statement に簡略化した。

- date: 2026-03-15
  branch: feat/ph6-2-session-gen
  scope: PH6-2-1 ProgramRepetitionRule 基盤整備（daily/weekly限定、weekly=1曜日、end_date必須）
  adopted: no
  classification: none
  targets: app/Models/ProgramRepetitionRule.php, app/Models/Program.php, app/Models/Location.php, app/Models/Staff.php, database/factories/ProgramRepetitionRuleFactory.php, database/migrations/2026_03_14_161547_enforce_program_repetition_rule_foundation_constraints_on_program_repetition_rules_table.php, tests/Feature/ProgramRepetitionRuleFoundationTest.php, tests/Unit/ProgramRepetitionRuleModelTest.php
  notes: ProgramRepetitionRule モデル・Factory・migration・テスト追加。cycle_type daily/weekly 限定、end_date 必須、weekly 時 day_of_week 必須、week_of_month 禁止を DB 制約とモデルガードで担保。親モデルに programRepetitionRules() リレーション追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings ログの unique 衝突表現を緩和）
  adopted: yes
  classification: PR限定
  targets: .cursor/review-feedback/log.md
  notes: 指摘は有効で、既存 entry の notes は unique 制約衝突を feature test で直接確認したように読め、実際の検証内容である「既存 singleton 行の再利用と件数維持」より強い表現だった。該当 notes を singleton 再利用経路の確認へ言い換え、ログの説明強度を実測事実に揃えた。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-fix の箇条書きインデント不整合を修正）
  adopted: yes
  classification: PR限定
  targets: .cursor/commands/review-fix.md
  notes: 指摘は一部有効で、問題の本質は markdownlint 方針そのものよりも同階層の箇条書きインデントが1行だけ他行と不一致だった点にあった。該当行を周囲の箇条書きと同じインデントへ揃え、文書全体の一貫性を回復した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（RFP-011 の分類根拠から機能固有名を除去）
  adopted: yes
  classification: PR限定
  targets: .cursor/rules/review-feedback-prevention.mdc
  notes: 指摘は有効で、RFP-011 の分類根拠に Category / ProgramType / Program / Location / Staff といった具体的な画面・モデル名が含まれており、本ファイル先頭の「汎用ルールのみ」という方針と緊張関係があった。分類根拠を「外部キー制約で削除失敗し得る CRUD 全般」に一般化し、スコープ定義と整合する表現へ調整した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-fix コマンドの章番号依存参照を解消）
  adopted: yes
  classification: PR限定
  targets: .cursor/commands/review-fix.md
  notes: 指摘は有効で、`.cursor/rules/review-feedback-prevention.mdc` の「6) 強制運用ゲート」のような章番号付き参照は、将来の見出し再編で古くなる余地があった。意味は変えずに「強制運用ゲート」セクション参照へ置き換え、構成変更に強い文面へ調整した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストに store 側 unique 回帰を追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: 指摘は有効で、`StoreAdditionalItemRequest` の `unique:additional_items,code` を直接通す失敗系が未カバーだった。兄弟 CRUD に合わせて store 側の重複 code 投稿を拒否する回帰テストを追加し、store / update で FormRequest を分離する CRUD は経路ごとの unique 失敗系を当該経路で直接持つよう RFP-016 を拡張した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストに update 側 validation 回帰を追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: 指摘は一部有効で、`UpdateAdditionalItemRequest` の `input_type` 失敗系は未カバーだったため update 側の invalid payload 回帰テストを追加した。一方で `code` の unique は既存の `test_update_rejects_duplicate_code` と同一 code 維持の update 成功ケースですでに検知可能だったため追加修正は見送った。store / update で FormRequest を分離する CRUD は代表的な失敗系を両経路で持つ汎用ルールを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-feedback validator の auto-promoted guard 誤昇格を防止）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php, tests/Tooling/ReviewFeedbackValidateTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: auto-promoted guard のテーマ判定が `User::ROLE_*` や `PHPDoc追加` の単独マッチで広すぎ、`adopted: no` / `classification: none` のログまで閾値集計していたため、対象パスと文脈語の両方でテーマを絞り込み、採用済みログだけを集計対象へ修正した。tooling test には別テーマの類似トークンでは昇格しないケースと、未採用・none ログが閾値へ加算されないケースを追加した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Update*Request の unique 除外を Rule::unique()->ignore() へ統一）
  adopted: yes
  classification: 汎用
  targets: app/Http/Requests/Admin/UpdateCategoryRequest.php, app/Http/Requests/Admin/UpdateProgramTypeRequest.php, app/Http/Requests/Admin/UpdateProgramRequest.php, app/Http/Requests/Admin/UpdateLocationRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateStaffRequest.php, tests/Feature/Admin/AdminCategoryCrudTest.php, tests/Feature/Admin/AdminProgramTypeCrudTest.php, tests/Feature/Admin/AdminProgramCrudTest.php, tests/Feature/Admin/AdminLocationCrudTest.php, tests/Feature/Admin/AdminAdditionalItemCrudTest.php, tests/Feature/Admin/AdminStaffCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: `Staff` だけを局所修正すると同系 Update FormRequest 間で記法が分岐するため、admin 配下の更新系 `unique` ルールを一括で `Rule::unique(...)->ignore($this->route(...))` へ統一した。Laravel docs の `ignore($model)` 推奨と route model binding 前提に合わせ、文字列連結より主キー名変更や条件拡張に追従しやすい形へ寄せた。あわせて 6 CRUD スイートへ「自分自身の code は更新可 / 他レコードの code は更新不可」の回帰テストを追加し、同一値除外と重複拒否の両方を明示した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストの status 定数化と alert 検証を保守しやすく調整）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php
  notes: status 直書き `'active'` / `'inactive'` は AdditionalItem::STATUS_ACTIVE / STATUS_INACTIVE へ寄せてモデル定義との追従性を改善。additional_item_type の alert 回帰テストは full HTML 固定ではなく、エラーメッセージ表示と `role=\"alert\"` の契約に絞った分割アサートへ変更し、view 整形差分だけで壊れにくくした。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest の一時Git/外部プロセス helper に責務PHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: createTemporaryGitRepository / stageFile / runCommand / deleteDirectory / runValidator は一時Gitリポジトリ作成、相対パス前提、外部プロセス実行、再帰削除など副作用が強く、既存の PHPDoc 方針に沿って責務・前提・更新方針を追記した。既存ルールでカバー済みのため再発防止ルールファイルの追加更新は不要。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-fix コマンドの log 更新条件を強制運用ゲートへ明示化）
  adopted: yes
  classification: PR限定
  targets: .cursor/commands/review-fix.md
  notes: `必要条件を満たす場合` だけでは `.cursor/review-feedback/log.md` 更新トリガーが不明確だったため、`.cursor/rules/review-feedback-prevention.mdc` の「6) 強制運用ゲート」を参照しつつ、`app/` `tests/` `database/` `routes/` `config/` `bootstrap/` `resources/` を含む場合と `classification: none` 記録義務を明示した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest の ROLE_MEMBER 正常系を単独ケース化し、review-fix 判定根拠を明文化）
  adopted: yes
  classification: PR限定
  targets: tests/Tooling/ReviewFeedbackValidateTest.php, .cursor/commands/review-fix.md
  notes: 指摘の問題意識は一部妥当で、mixed fixture 自体よりも guard が実際に見る `'role' => ...` 形を happy path が再現していない点が弱かったため、`User::ROLE_ADMIN` / `User::ROLE_MEMBER` をそれぞれ単独ケース化。あわせて `/review-fix` コマンドへ「有効 / 一部有効 / 無効」の根拠付き判定を必須化し、`妥当そう` のような推測表現を禁止した。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest に auto-promoted guard の正常系を追加）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: auto-promoted role guard と controller PHPDoc guard は違反検知だけでなく false positive 防止の正常系も必要なため、`User::ROLE_ADMIN` / `User::ROLE_MEMBER` を使う staged feature test と、隣接 PHPDoc を持つ staged admin controller が通過する happy path を追加。既存の失敗系と合わせて tooling validator の回帰網羅を補強。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff の gender 許容値をフォーム選択肢へ整合）
  adopted: yes
  classification: 汎用
  targets: app/Http/Requests/Admin/StoreStaffRequest.php, app/Http/Requests/Admin/UpdateStaffRequest.php, tests/Feature/Admin/AdminStaffCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: Staff フォームの gender は male / female / other の3択だが、store / update の FormRequest が広い string 許容になっていたため、両方を `in:male,female,other` へ統一。細工した未定義値の store / update を feature test で拒否し、固定選択 UI とサーバー側許容値を揃える汎用ルールを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem の hidden 項目エラー表示へ role="alert" を追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/additional-items/_form.blade.php, tests/Feature/Admin/AdminAdditionalItemCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: fixed hidden 値で表示している additional_item_type のエラー表示だけ `role="alert"` が漏れていたため追加。対象フィールド単位で alert role を確認する feature test を追加し、custom error markup でも即時通知アクセシビリティを揃える汎用ルールを追記。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Locationフォームのstatus値をモデル定数参照へ統一）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/locations/_form.blade.php
  notes: Location フォームの status 選択肢で `'active'` / `'inactive'` を直書きしていたため、Location::STATUS_ACTIVE / STATUS_INACTIVE に置換。UI と FormRequest / モデル定義の single source of truth を揃え、将来の定数変更時の不整合を防止。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest のログ entry PHPDoc を配列形状型へ厳密化）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: validLogEntry() の overrides と buildLogContent() の entries を固定キーの配列形状型へ更新し、defaults マージ後の entry / merged も required key shape として注釈。RFP-002 に沿って typo やキー欠落を静的に拾いやすくした。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/ProgramTypeController.php, resources/views/admin/program-types/_delete_error_row.blade.php, tests/Feature/Admin/AdminProgramTypeCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: ProgramType削除でFK制約違反（programs.program_type_id）を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連Programあり時の削除失敗をFeatureテストで追加検証し、マスタ系CRUDの削除失敗パターンを汎用ルールへ昇格。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（HTMX部分描画テストのDOCTYPE検証 false positive を修正）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminCategoryCrudTest.php, tests/Feature/Admin/AdminProgramTypeCrudTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: `assertDontSee()` の既定値では検索文字列がHTMLエスケープされるため、`<!DOCTYPE html>` の不在確認が false positive になっていた。Category / ProgramType の HTMX 部分描画テストを `assertDontSee('<!DOCTYPE html>', false)` へ修正し、再発防止ルールを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program CRUD のアクセス制御テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramCrudTest.php
  notes: admin.programs.index に対する guest / non-admin のアクセス制御を Program CRUD テストへ追加し、Category / ProgramType と同水準の認可回帰を確保。HTMXヘッダー指定の指摘は、このファイル自体は withHeader() を使用済みのため見送り。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（LocationController の削除制約違反 helper に責務PHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/LocationController.php
  notes: respondDeleteConstraintViolation() に責務・前提・更新方針を示す PHPDoc を追加し、HTMX と通常リクエストで応答を分岐する意図を読み取りやすくした。挙動変更はなし。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StaffController の削除制約違反 helper に責務PHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StaffController.php
  notes: respondDeleteConstraintViolation() に責務・前提・更新方針を示す PHPDoc を追加し、HTMX と通常リクエストで応答を分岐する意図を読み取りやすくした。挙動変更はなし。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ReviewFeedbackValidateTest の helper デフォルト意図を PHPDoc で明示）
  adopted: yes
  classification: 汎用
  targets: tests/Tooling/ReviewFeedbackValidateTest.php
  notes: validLogEntry は phase-1 検証向けに adopted=no / classification=none を既定値とし、buildLogContent は auto-promoted guard 用に adopted=yes / classification=汎用 を既定値とする意図を PHPDoc で明示。両 helper の責務差をコメントで読み取りやすくした。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-feedback validator の閾値定数化と staged PHP helper の PHPDoc 補強）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php
  notes: auto-promoted guard の threshold を意図が分かる定数へ置換し、再発回数の意味をコメントで明示。get_staged_php_files() の PHPDoc に $repoRoot / $paths / $suffix の役割を追記し、suffix フィルタの意図を文書化。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program削除失敗パーシャルへ role="alert" を追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/programs/_delete_error_row.blade.php, tests/Feature/Admin/AdminProgramCrudTest.php
  notes: HTMX で動的挿入される Program 削除失敗メッセージに role="alert" を追加し、スクリーンリーダーへの即時通知を改善。削除失敗時のエラー行に role 属性が含まれることを feature test で確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff の重複code投稿時の回帰テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStaffCrudTest.php
  notes: staffs.code の unique 制約衝突時に code フィールドへ専用メッセージが返り、重複レコードが作成されないことを feature test で明示。既存の StoreStaffRequest の unique ルールに対する回帰防止を追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Location の重複code投稿時の回帰テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminLocationCrudTest.php
  notes: locations.code の unique 制約衝突時に code エラーとなることを feature test で明示。既存の StoreLocationRequest の unique ルールに対する回帰防止を追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff削除制約エラーメッセージを包括表現へ調整）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StaffController.php, tests/Feature/Admin/AdminStaffCrudTest.php
  notes: Staff の FK 制約違反メッセージは実データ参照先を狭めず、関連データ全体を含意する包括表現へ変更。通常削除と HTMX 削除の両方で新文言が返ることを feature test で確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettingsFactory definition のPHPDocを配列形状型へ更新）
  adopted: yes
  classification: 汎用
  targets: database/factories/StoreSettingsFactory.php
  notes: StoreSettingsFactory の definition() は固定キーの設定配列を返すため、array<string, mixed> から array{singleton_key, ...} の配列形状型へ更新。項目追加・削除時の差分検知と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings の resolveSettings に責務PHPDocを追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StoreSettingsController.php
  notes: StoreSettings の主要 private helper である resolveSettings() に、責務・前提・更新方針を示す PHPDoc を追加。singleton 行の確保メソッドである意図を明示し、レビュー時の解釈ずれを防止。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings の unique衝突経路を回帰テストで明示）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: createOrFirst 前提の既存 singleton 行再利用を明示するため、update 実行後も件数が1件のままで既存IDが再利用されることを検証する回帰テストを追加。singleton 再利用経路を feature test で確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（FK制約違反の判定ロジックをControllerへ共通化）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Controller.php, app/Http/Controllers/Admin/LocationController.php, app/Http/Controllers/Admin/StaffController.php, app/Http/Controllers/Admin/ProgramController.php
  notes: Location/Staff/Program で重複していた isForeignKeyConstraintViolation() をベース Controller の protected helper へ集約し、削除失敗時の応答ロジックは各コントローラに維持。既存 feature test で通常削除・通常失敗・HTMX失敗の回帰を確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（review-feedback validator の staged PHP 取得処理を共通化）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php, tests/Tooling/ReviewFeedbackValidateTest.php
  notes: auto-promoted guard 分岐の最後のケースにも明示 continue を追加し、5つの get_staged_php_files_in_* 関数で重複していた git diff + PHP サフィックス判定を共通ヘルパーへ集約。temp git repo を使う tooling テストを追加し、admin feature test / admin controller の auto-promoted guard が共通化後も動作することを確認。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/ProgramController.php, resources/views/admin/programs/_delete_error_row.blade.php, tests/Feature/Admin/AdminProgramCrudTest.php
  notes: Program削除でFK制約違反（lesson_sessions / program_repetition_rules）を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連LessonSessionあり時の削除失敗をFeatureテストで追加検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（残りのinvalid-feedbackへ role="alert" を横展開）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/partials/_master_code_name_sort_status_fields.blade.php, resources/views/admin/additional-items/_form.blade.php, resources/views/admin/locations/_form.blade.php, resources/views/admin/staffs/_form.blade.php, resources/views/admin/store-settings/edit.blade.php, resources/views/auth/login.blade.php, resources/views/auth/register.blade.php, resources/views/auth/forgot-password.blade.php, resources/views/auth/reset-password.blade.php, tests/Feature/Admin/AdminCategoryCrudTest.php, tests/Feature/Admin/AdminStoreSettingsTest.php, tests/Feature/AuthViewsTest.php
  notes: programs と汎用エラーパーシャル以外に残っていた invalid-feedback へ role="alert" を追加し、admin/auth の主要入力画面でスクリーンリーダー通知を一貫化。共有 partial・単独管理画面・認証画面の回帰テストを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Programフォームのinvalid-feedbackへ role="alert" 追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/programs/_form.blade.php, tests/Feature/Admin/AdminProgramCrudTest.php
  notes: programs フォームの各 invalid-feedback に role="alert" を付与し、スクリーンリーダーでの即時通知を改善。必須項目エラー表示で role 属性が描画される回帰テストを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType更新で同一code許可の回帰テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramTypeCrudTest.php
  notes: UpdateProgramTypeRequest が unique:program_types,code,$id で同一レコードを除外する挙動に合わせ、test_update_allows_same_code を追加して Category CRUD テストとの整合性を確保。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettings更新経路をトランザクション+行ロック化）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StoreSettingsController.php, tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: StoreSettings 更新時に ConnectionInterface の transaction 内で createOrFirst 後の単一行を lockForUpdate で再取得し、RFP-003 の競合耐性パターンへ整合。設定未作成状態からの update 回帰テストを追加。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（バリデーションエラー表示に role="alert" を追加）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/partials/_errors.blade.php
  notes: スクリーンリーダーで即時に伝わるよう alert 要素に role="alert" を付与し、アクセシビリティを向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/StaffController.php, resources/views/admin/staffs/_delete_error_row.blade.php, tests/Feature/Admin/AdminStaffCrudTest.php
  notes: Staff削除でFK制約違反を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連LessonSessionあり時の削除失敗をFeatureテストで追加検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Location削除時の外部キー制約エラーを利用者向けに処理）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/LocationController.php, resources/views/layouts/admin.blade.php, resources/views/admin/locations/_delete_error_row.blade.php, tests/Feature/Admin/AdminLocationCrudTest.php
  notes: Location削除でFK制約違反（lesson_sessions / program_repetition_rules）を捕捉し、通常リクエストはセッションerror、HTMXは対象行のエラー表示へ分岐。関連データあり時の削除失敗をFeatureテストで追加検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramControllerのフォーム取得共通化と主要アクションPHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/ProgramController.php
  notes: create/edit で重複していたカテゴリ・プログラム種別取得を resolveFormMasterData() へ集約。index/create/store/edit/update/destroy に責務・前提・更新方針を示すPHPDocを追加し、規約準拠と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItemController主要アクションへPHPDoc追加）
  adopted: yes
  classification: 汎用
  targets: app/Http/Controllers/Admin/AdditionalItemController.php
  notes: index/create/store/edit/update/destroy に責務・前提・更新方針を示す短いPHPDocを追加し、コントローラの規約準拠と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Staff CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStaffCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Location CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminLocationCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminAdditionalItemCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Program CRUDテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramCrudTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（StoreSettingsテストの管理者ロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: setUp の role 文字列リテラル（admin）を User::ROLE_ADMIN へ置換し、Userモデル定数との整合性を確保。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（Category CRUDテストのロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminCategoryCrudTest.php
  notes: role の文字列リテラル（admin/member）を User::ROLE_ADMIN / User::ROLE_MEMBER へ置換し、モデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（AdditionalItem FormRequestのstatus定数統一）
  adopted: yes
  classification: 汎用
  targets: app/Http/Requests/Admin/StoreAdditionalItemRequest.php, app/Http/Requests/Admin/UpdateAdditionalItemRequest.php
  notes: status の in ルールでハードコード（active/inactive）を廃止し、AdditionalItem::STATUS_ACTIVE / STATUS_INACTIVE を使用してモデル定数と一貫化。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType CRUDテストのロール指定を定数化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramTypeCrudTest.php
  notes: role の文字列リテラル（admin/member）を User::ROLE_ADMIN / User::ROLE_MEMBER へ置換し、モデル定数との整合性と保守性を向上。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（ProgramType CRUDテストの観点拡充）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/Admin/AdminProgramTypeCrudTest.php
  notes: HTMX部分レンダリング、フォーム表示、認可（guest/non-admin）、unique code と invalid status の異常系を追加し、AdminCategoryCrudTest と同水準のCRUD観点へ整合。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（additional_item_type 固定値UIの簡素化）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/additional-items/_form.blade.php
  notes: 選択肢が1件固定の additional_item_type を select から hidden + 表示テキストへ変更し、UIノイズを削減。値は member_profile を固定送信してバリデーション意図を維持。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（programs フォーム数値入力の step 明示）
  adopted: yes
  classification: 汎用
  targets: resources/views/admin/programs/_form.blade.php
  notes: 整数前提項目（duration_minutes / price / point_cost / ticket_cost）に step=\"1\" を追加し、UI入力制約とサーバー側 integer バリデーションの意図を一致させた。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PR指摘対応（store_settings の単一レコード制約をDBで担保）
  adopted: yes
  classification: 汎用
  targets: database/migrations/2026_03_02_080811_create_store_settings_table.php, app/Http/Controllers/Admin/StoreSettingsController.php, app/Models/StoreSettings.php, database/factories/StoreSettingsFactory.php, tests/Feature/Admin/AdminStoreSettingsTest.php
  notes: singleton_key を単一値 enum（singleton）+ UNIQUE 制約で追加し、StoreSettings 初期化を createOrFirst（singleton_key=singleton）へ変更。設定画面の連続アクセスで常に1行に維持されることをテストで検証。

- date: 2026-03-02
  branch: feat/ph6-1-master-crud
  scope: PH6-1 マスタ系管理画面 (HTMX) + HTMXインフラ + AdditionalItem/StoreSettingsモデル新規作成
  adopted: no
  classification: none
  targets: routes/web.php, resources/views/layouts/admin.blade.php, public/assets/vendor/htmx/htmx.min.js, app/Http/Controllers/Admin/*.php, app/Http/Requests/Admin/*.php, app/Models/AdditionalItem.php, app/Models/StoreSettings.php, database/factories/AdditionalItemFactory.php, database/factories/StoreSettingsFactory.php, database/migrations/2026_03_02_080811_create_store_settings_table.php, resources/views/admin/**/*.blade.php, tests/Feature/Admin/*.php
  notes: HTMX自己ホスト配置とCSRF自動付与。Category/ProgramType/Program/Location/Staff/AdditionalItem の6リソースCRUDとStoreSettings単行編集。全7コントローラ・14 FormRequest・全ビュー（index/_table/create/edit/_form）。AdditionalItem/StoreSettingsモデル+Factory新規作成。store_settingsマイグレーション追加。49テスト（認可・CRUD・バリデーション・HTMX部分更新）追加、全176テスト通過。

- date: 2026-03-02
  branch: feat/ph13-2-1-admin-layout
  scope: PR指摘対応（管理画面CSS規約準拠とカード描画の重複解消）
  adopted: yes
  classification: 汎用
  targets: public/assets/css/app.css, public/assets/css/pages/admin.css, resources/views/layouts/admin.blade.php, resources/views/admin/dashboard.blade.php
  notes: 管理画面CSSを pages/admin.css へ移動し、BEM命名 + app.cssトークン参照へ統一。ダッシュボードサマリーカードを配列+@foreach化して重複マークアップを削減。

- date: 2026-03-02
  branch: feat/ph13-2-1-admin-layout
  scope: PH13-2-1 管理用共通レイアウトとダッシュボードの実装
  adopted: no
  classification: none
  targets: resources/views/layouts/admin.blade.php, resources/views/admin/dashboard.blade.php, public/assets/css/pages/admin.css, routes/web.php, tests/Feature/AdminDashboardTest.php, tests/Feature/AdminAuthorizationTest.php
  notes: 管理用レイアウト（サイドバー+ヘッダー+メインコンテンツ）を作成。/admin をビュー返却に変更。AdminDashboardTest で描画・ナビ・ログアウト確認。AdminAuthorizationTest のアサーションを日本語化に合わせて更新。

- date: 2026-03-02
  branch: feat/ph13-1-1-admin-auth-guard
  scope: PR指摘対応（nullロールテストをFactoryで統一）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/AdminAuthorizationTest.php
  notes: test_null_role_is_forbidden_on_admin_route で new User 手動生成を User::factory()->makeOne(['role' => null]) に置換。DB制約上 role は NOT NULL のため create ではなく make を使用。

- date: 2026-03-02
  branch: feat/ph13-1-1-admin-auth-guard
  scope: PH13-1-1 管理者認可（Gate）と /admin 保護ルート
  adopted: no
  classification: none
  targets: app/Providers/AppServiceProvider.php, routes/web.php, tests/Feature/AdminAuthorizationTest.php
  notes: Gate::define('access-admin') を追加し、/admin を auth + can:access-admin で保護。未ログイン/非管理者/管理者/境界ロール（空・NULL・未知値）のFeatureテストを追加。

- date: 2026-03-02
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（resolveSubscriptionLine の責務分離）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php, tests/Feature/SubscriptionPaymentWebhookTest.php
  notes: resolveSubscriptionLine で subscription 不一致/price.id 欠落を除外しないようにし、呼び出し側の mismatch / missing price バリデーション分岐を到達可能化。mismatched_line_subscription テスト期待値を具体的な mismatch メッセージに更新。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（RFP-005 Subscription テストデータ作成をFactoryへ統一）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/SubscriptionPaymentWebhookTest.php
  notes: Subscription::query()->create を 6 箇所すべて Subscription::factory()->create へ置換。Cashier同梱Factoryを利用し新規Factory作成は不要。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（RFP-003 createOrFirst 後の lockForUpdate 再取得）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php
  notes: course_entitlements を createOrFirst 後に lockForUpdate で再取得し、子要素作成を同一ロックスコープで実行。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PR指摘対応（RFP-002 PHPDoc・エッジケーステスト）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php, tests/Feature/SubscriptionPaymentWebhookTest.php
  notes: $payload 配列形状型 @param 追加。unknown_price / per_category_no_categories のエッジケーステスト追加。per_category カテゴリ未設定時の部分保存不整合を修正（付与前にカテゴリチェック）。

- date: 2026-03-01
  branch: feat/ph5-5-subscription
  scope: PH5-5 サブスク購読Webhookと周期枠付与ジョブ
  adopted: no
  classification: none
  targets: app/Jobs/ProcessSubscriptionPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, app/Jobs/RouteCheckoutSessionWebhookJob.php, database/migrations/2026_03_01_150116_add_unique_period_constraint_to_course_entitlements_table.php, tests/Feature/SubscriptionPaymentWebhookTest.php, config/cashier.php
  notes: invoice.payment_succeeded で course_entitlements を付与し、checkout.session.* (mode=subscription) は専用Jobへルーティング。user+plan+period の unique 制約で重複付与を防止。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PR Nitpick 対応（review-feedback-validate PHPDoc・複数行属性スキップ）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php
  notes: is_rfp009_target_method に PHPDoc 追加。複数行 PHP 8 属性のスキップを skip_attribute_blocks/find_attribute_start_index で対応。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PR Nitpick 対応（release 失敗時の再送出・RFP-009 ガード追加）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/RouteCheckoutSessionWebhookJob.php, scripts/review-feedback-validate.php
  notes: release() 失敗時に例外を再送出し retry/failed() に委譲。RFP-009 を Phase 4 として review-feedback-validate.php に追加。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: Webhook 受信と購入レコード作成の競合リスク対策（信頼性）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/RouteCheckoutSessionWebhookJob.php, app/Providers/AppServiceProvider.php, app/Jobs/ProcessPrepaidPaymentWebhookJob.php
  notes: exists() 即 failed を廃止。RouteCheckoutSessionWebhookJob を常時 dispatch し、対象未検出時は遅延リトライ。markFailed は status=received 時のみ更新。クロージャ PHPDoc を @var に変更。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PR Nitpick 対応（payment_status 検証・遅延決済対応・async_payment_succeeded）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessPrepaidPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, config/cashier.php, tests/Feature/PrepaidPaymentWebhookTest.php
  notes: payment_status を検証（unpaid はスキップ、paid/no_payment_required のみ付与）。checkout.session.async_payment_succeeded を購読・処理対象に追加。handle PHPDoc に遅延決済の扱いを明記。テスト追加（unpaid スキップ・async_succeeded 付与）。

- date: 2026-02-25
  branch: feat/ph5-4-prepaid-webhook
  scope: PH5-4 プリペイド Webhook 処理
  adopted: no
  classification: none
  targets: app/Jobs/ProcessPrepaidPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, tests/Feature/PrepaidPaymentWebhookTest.php
  notes: checkout.session.completed のプリペイド向け処理。event_id 冪等、balance_transactions 付与、idempotency_key で二重付与防止。体験/プリペイドの振り分けを AppServiceProvider に追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（refunded ファクトリ・setUp 集約・markRefunded ガード）
  adopted: yes
  classification: 汎用
  targets: database/factories/TrialApplicationFactory.php, tests/Feature/ProcessTrialRefundJobTest.php, app/Jobs/ProcessTrialRefundJob.php
  notes: TrialApplicationFactory に refunded() ステート追加。ProcessTrialRefundJobTest で StripeRefundService モックを setUp に集約。markRefunded に STATUS_REFUNDED ガード追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（payment_intent 欠落時の運用検知ログ）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessTrialRefundJob.php, tests/Feature/ProcessTrialRefundJobTest.php
  notes: paymentIntentId 空時に Log::warning を追加。キュー成功扱いでも運用検知可能に。テストに Log::shouldReceive 検証を追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（ProcessTrialRefundJob PHPDoc・対象外スキップテスト・RFP-009）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessTrialRefundJob.php, tests/Feature/ProcessTrialRefundJobTest.php, .cursor/rules/review-feedback-prevention.mdc
  notes: handle/buildRefundIdempotencyKey/markRefunded/markRefundFailed に PHPDoc 追加。並行実行設計意図を handle に記載。RFP-009 追加。対象外スキップ（id 存在しない・既に refunded）テスト追加。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（ProcessTrialRefundJob 例外再送出でキュー再試行を有効化）
  adopted: yes
  classification: 汎用
  targets: app/Jobs/ProcessTrialRefundJob.php, tests/Feature/ProcessTrialRefundJobTest.php
  notes: catch 内で markRefundFailed 後に throw $exception を追加。tries/backoff が有効化。テストは例外再送出を検証する形に更新。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PR Nitpick 対応（PHPDoc・リトライ・STATUS_REFUND_FAILED・PII・戻り値型）
  adopted: yes
  classification: 汎用
  targets: app/Services/StripeRefundService.php, app/Models/TrialApplication.php, app/Jobs/ProcessTrialRefundJob.php, app/Jobs/ProcessTrialPaymentWebhookJob.php, app/Providers/AppServiceProvider.php, tests/Feature/TrialPaymentWebhookTest.php
  notes: StripeRefundService PHPDoc・TrialApplication BelongsTo PHPDoc・ProcessTrialRefundJob tries/backoff・ProcessTrialPaymentWebhookJob tries=1・STATUS_REFUND_FAILED 冪等ガード・event_id 欠落時の PII 排除（payload→event_type/checkout_session_id）・postWebhook 戻り値型 TestResponse。

- date: 2026-02-24
  branch: feat/ph5-3-trial-webhook
  scope: PH5-3 体験決済 Webhook 処理（予約確定/返金: refund_pending/failed + Idempotency-Key）
  adopted: no
  classification: none
  targets: app/Jobs/ProcessTrialPaymentWebhookJob.php, app/Jobs/ProcessTrialRefundJob.php, app/Models/TrialApplication.php, app/Providers/AppServiceProvider.php, app/Services/StripeRefundService.php, config/cashier.php, database/factories/TrialApplicationFactory.php, tests/Feature/ProcessTrialRefundJobTest.php, tests/Feature/TrialPaymentWebhookTest.php
  notes: checkout.session.completed の Webhook 処理。event_id 冪等、予約確定 or refund_pending + Refund Job。返金は Idempotency-Key で冪等化。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PR Nitpick 対応（リプレイ攻撃境界テスト追加）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: タイムスタンプ期限切れ（time()-301）の境界テスト追加。makeStripeSignatureHeader に ?int $timestamp を追加。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PR Nitpick 対応（PHPDoc・setUp 集約）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: makeStripeSignatureHeader に PHPDoc 追加。config()->set を setUp へ集約（RFP-007）。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PR Nitpick 対応（CSRF除外絞り込み・weird path テスト追加）
  adopted: yes
  classification: 汎用
  targets: bootstrap/app.php, tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: stripe/* → stripe/webhook に限定。署名ヘッダ欠落の feature テスト追加。

- date: 2026-02-24
  branch: feat/ph5-2-webhook-signature
  scope: PH5-2 Stripe Webhook 署名検証（Stripe-Signature）
  adopted: no
  classification: none
  targets: bootstrap/app.php, tests/Feature/StripeWebhookSignatureVerificationTest.php
  notes: stripe/* の CSRF 除外。署名検証の feature テスト（無効/有効署名）追加。

- date: 2026-02-24
  branch: feat/ph5-1-cashier-install
  scope: PR Nitpick 対応 & RFP-008（インラインコメント禁止）の自動検知追加
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-guard.sh, .env.example, config/cashier.php, .cursor/rules/review-feedback-prevention.mdc, scripts/review-feedback-validate.php
  notes: guard: REPO_ROOT/LOG_PATH 先頭集約・git add $LOG_PATH。.env.example: `CASHIER_*` を `STRIPE_*` より前に。cashier.php: コメントアウト import と // Supported: 削除。RFP-008 追加と Phase 3 検知実装。

- date: 2026-02-24
  branch: feat/ph5-1-cashier-install
  scope: PH5-1 Cashier インストール & 設定 / log.md 自動追記
  adopted: no
  classification: none
  targets: composer.json, app/Models/User.php, config/services.php, config/cashier.php, .env.example, database/migrations/*, scripts/review-feedback-guard.sh
  notes: Cashier 導入。User に Billable と $hidden 追加。Stripe 環境変数。guard で log.md 未更新時にテンプレート自動追記。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: PRレビュー指摘対応（インラインコメント→PHPDoc）
  adopted: yes
  classification: 汎用
  targets: scripts/review-feedback-validate.php
  notes: CodeRabbit 指摘。Phase 1/Phase 2 のインラインコメントを PHPDoc ブロックへ置換。review-feedback-prevention 2.5 方針に準拠。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: log.md 自動活用 Phase 1+2 実装
  adopted: no
  classification: none
  targets: scripts/review-feedback-validate.php, scripts/review-feedback-guard.sh, tests/Tooling/ReviewFeedbackValidateTest.php
  notes: Phase 1: log 必須キー・classification・adopted・日付・targets の検証。Phase 2: RFP-001 チェッカー（->refresh()/->fresh() 禁止）。guard に統合。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: PRレビュー指摘対応（update 直後の refresh 削除）
  adopted: yes
  classification: 汎用
  targets: app/Services/ReservationService.php
  notes: RFP-001 趣旨に沿い、update() 直後の不要な refresh() を削除。Eloquent の update() は in-memory 属性を更新するため追加 SELECT は不要。

- date: 2026-02-23
  branch: feat/ph4-3-cancel-logic
  scope: PH4-3 予約キャンセル・巻き戻しロジック実装
  adopted: no
  classification: none
  targets: app/Services/ReservationService.php, tests/Feature/ReservationServiceTest.php
  notes: ReservationService::cancel() を実装。トランザクション・lockForUpdate・冪等・カウンタデクリメント・異常系テストを追加。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: ReservationService / ReservationServiceTest（レビュー対応一式）
  adopted: yes
  classification: 汎用
  targets: .cursor/rules/review-feedback-prevention.mdc (RFP-001..RFP-007)
  notes: fresh削除、TODO整理、配列形状型、setUp集約、DBファサード回避、同時実行競合、識別子生成TOCTOU対策、テストのFactory化/uniqid排除を汎用ルールとして反映。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: レビュー蓄積漏れ再発防止の運用強化
  adopted: yes
  classification: 汎用
  targets: .cursor/rules/review-feedback-prevention.mdc, scripts/review-feedback-guard.sh, .githooks/pre-commit, .cursor/commands/commit-only.md, .cursor/commands/commit-push.md
  notes: コード変更コミット時に review-feedback log 更新を必須化し、pre-commit/hook と commit コマンド手順で機械的に強制する運用へ変更。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: PRレビュー指摘対応（コメント対応）
  adopted: yes
  classification: 汎用
  targets: README.md, scripts/install-review-feedback-hook.sh, scripts/review-feedback-guard.sh, .cursor/commands/commit-only.md, .cursor/commands/commit-push.md, app/Models/LessonSession.php, tests/Feature/ReservationServiceTest.php
  notes: フック有効化手順のドキュメント化、core.hooksPath方式への変更、case文統合、ガード文言の条件付き明確化、LessonSession belongsTo追加、ModelNotFoundExceptionテスト追加。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: PRレビュー指摘対応（追加）
  adopted: yes
  classification: 汎用
  targets: README.md, .cursor/commands/commit-push.md, app/Models/LessonSession.php, tests/Feature/ReservationServiceTest.php
  notes: フック目的・適用対象の明記、必須ガード見出し分離と||exit 1追加、BelongsToジェネリック型PHPDoc、非存在IDの動的生成。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: レビュー指摘対応（ロールバック検証とresources対象化）
  adopted: yes
  classification: 汎用
  targets: tests/Feature/ReservationServiceTest.php, scripts/review-feedback-guard.sh, .cursor/rules/review-feedback-prevention.mdc, .cursor/commands/commit-only.md, .cursor/commands/commit-push.md, README.md
  notes: キャパ超過時の例外テストで予約未作成とカウンタ不変を確認し、レビュー蓄積ガード対象にresources/を追加して関連ルール・ドキュメントの記載を統一。

- date: 2026-02-23
  branch: feat/ph4-2-booking-logic
  scope: PRレビュー指摘対応（commit-push Bセクション）
  adopted: yes
  classification: 汎用
  targets: .cursor/commands/commit-push.md
  notes: Bセクションの git add -A に || exit 1 を追加し、Aセクションと整合。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PH6-2-2 生成候補列挙サービス実装
  adopted: no
  classification: none
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php, tests/Unit/ProgramRepetitionRuleSessionCandidateServiceTest.php
  notes: daily/weekly の候補日時列挙サービスを追加。start_date/end_date/start_time/day_of_week の不正値を fail fast で拒否し、境界値と異常系の unit test を追加。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（private helper PHPDoc 追加）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: private helper に責務・前提・更新方針の PHPDoc を追加し、review-feedback-prevention 2.5 の既存方針に合わせた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（enumerate エントリポイント PHPDoc 追加）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: エントリポイント enumerate() に責務・前提・更新方針の PHPDoc を追加し、review-feedback-prevention 2.5 に合わせた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（enumerateDaily の $rule パラメータ整理）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: day_of_week 検証を enumerate() に移動し、enumerateDaily() から $rule を削除。enumerateWeekly() とシグネチャを揃えた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-candidate
  scope: PRレビュー指摘対応（lock/transaction/idempotency PHPDoc 明示）
  adopted: yes
  classification: 汎用
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php
  notes: エントリポイント PHPDoc に Lock/Transaction/Idempotent を明示し、RFP-009 に合わせた。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（reservation_management 作成を relation 経由へ統一）
  adopted: yes
  classification: PR限定
  targets: app/Services/ProgramRepetitionRuleSessionGenerationService.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。LessonSession に reservationManagement() を追加済みのため、関連行作成は relation の create() を使う方が外部キーの手詰めを避けて意図を明確にできる。一方で、他サービスとの書き方統一という根拠は現状コードベースでは強くなかったため、その点は採用せず、生成処理の最小差分リファクタに限定して対応した。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（reservation_management 作成失敗時の transaction rollback テスト追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。今回の主題は lesson_sessions と reservation_management を同一 transaction で扱う点にあるため、ReservationManagement::creating で例外を発生させたときに generate() 全体がロールバックされ、両テーブルが 0 件のままになる失敗系テストを追加した。既存実装の挙動確認が目的で、本番コードの変更は行っていない。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（reservation_management 関連の存在検証を false positive にならない形へ修正）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。従来の生成テストは relation()->value(...) の戻り値を int キャストしており、関連行が未作成でも null が 0 になって assertion を通過し得た。daily/weekly の正常系テストで reservationManagement を eager load し、関連が ReservationManagement インスタンスとして存在することを先に検証したうえでカウンタ値を読む形へ修正した。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（重複判定 helper 名を starts_at スコープへ明確化）
  adopted: yes
  classification: PR限定
  targets: app/Services/ProgramRepetitionRuleSessionGenerationService.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。現状実装は `program_id` / `location_id` / `staff_id` で絞り込んだ既存セッション集合に対して `starts_at` をキー化しているため、現在の挙動としては誤判定しない。一方で `buildCandidateKey()` と `existingCandidateKeys()` という名前だと 4 項目の複合キーを返しているように読めて、将来の流用や検索条件変更時に前提を誤読しやすかった。重複判定ロジック自体は変えず、`buildScopedStartsAtKey()` / `existingScopedStartsAtKeys()` へ改名し、PHPDoc と変数名でも「rule scope 内の starts_at キー」であることを明示した。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（lesson_sessions の concrete slot 複合 UNIQUE を追加）
  adopted: yes
  classification: PR限定
  targets: database/migrations/2026_03_15_164314_add_concrete_slot_unique_to_lesson_sessions_table.php, tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`ProgramRepetitionRuleSessionGenerationService` は `program_id` / `location_id` / `staff_id` / `starts_at` を concrete slot identity として重複 skip していたが、`lesson_sessions` の DB スキーマには `code` の UNIQUE しかなく、この4項目の一意性を物理保証していなかった。Laravel docs の複合 unique index 方針に合わせて `lesson_sessions_concrete_slot_unique` を追加し、同一 slot の 2 件目 insert が `QueryException` で拒否される回帰テストを追加した。現行 DB には同一4項目の重複行がないことも事前確認済み。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（concrete slot UNIQUE 競合を skip として吸収）
  adopted: yes
  classification: PR限定
  targets: app/Services/ProgramRepetitionRuleSessionGenerationService.php, tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`lesson_sessions` の concrete slot UNIQUE 競合が発生したときに例外がそのまま上位へ伝播する懸念は妥当だった。一方で `UNIQUE 制約違反をすべて skip` として握りつぶすと `code` 競合など別原因まで隠してしまう。`createLessonSession()` の直後だけで `UniqueConstraintViolationException` を補足し、同一 `program_id` / `location_id` / `staff_id` / `starts_at` の既存行が実際に見つかった場合に限って `skipped_count` へ計上して続行するよう補強した。競合後に同一 slot 行が存在しない場合は従来どおり例外を再送出する。

- date: 2026-03-15
  branch: feat/ph6-2-generation-persistence
  scope: PRレビュー指摘対応（UNIQUE 制約テストの DB 依存メッセージを除去）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/ProgramRepetitionRuleSessionGenerationServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。現行テスト環境は sqlite 固定のため直ちに不安定ではなかったが、`'UNIQUE constraint failed'` への部分一致は SQLite 固有で、DB を切り替えると brittle だった。重複 concrete slot を DB が拒否するという契約自体が主目的なので、driver 依存の文言確認をやめて `UniqueConstraintViolationException` の型と件数維持だけを検証する形へ縮小した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-action
  scope: [PH6-2-4] 管理者向け生成アクション追加
  adopted: no
  classification: none
  targets: app/Http/Controllers/Admin/ProgramRepetitionRuleGenerationController.php, routes/web.php, tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php, .cursor/review-feedback/log.md
  notes: 新規実装のため採用レビュー指摘はなし。管理者専用の手動生成 route/controller を追加し、guest/member/admin・0件境界・404・再実行時の skip 件数を feature test で確認した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-action
  scope: PRレビュー指摘対応（LessonSession の整数属性 cast を明示）
  adopted: yes
  classification: PR限定
  targets: app/Models/LessonSession.php, tests/Unit/LessonSessionModelTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php` の `capacity` / `trial_capacity` に対する strict 比較は、SQLite では通っても DB ドライバ差で不安定化し得た。一方で `assertEquals` へ弱めるより、`LessonSession` 自体が整数として扱うべき属性の型契約を明示する方が適切だったため、`program_id` / `location_id` / `staff_id` / `capacity` / `trial_capacity` を integer cast に追加し、unit test で固定した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: [PH6-2-5] 管理画面UI実装（繰り返し設定CRUD、1件生成、生成結果表示）
  adopted: no
  classification: none
  targets: app/Http/Controllers/Admin/ProgramRepetitionRuleController.php, app/Http/Controllers/Admin/ProgramRepetitionRuleGenerationController.php, app/Http/Requests/Admin/StoreProgramRepetitionRuleRequest.php, app/Http/Requests/Admin/UpdateProgramRepetitionRuleRequest.php, routes/web.php, resources/views/layouts/admin.blade.php, resources/views/admin/program-repetition-rules/, tests/Feature/Admin/AdminProgramRepetitionRuleCrudTest.php, tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php, .cursor/review-feedback/log.md
  notes: 新規実装のため採用レビュー指摘はなし。既存の admin CRUD パターンに合わせて ProgramRepetitionRule の resource route/controller/FormRequest/Blade を追加し、1件生成アクションの戻り先を一覧へ変更して結果フラッシュを表示するようにした。関連する CRUD・HTMX・generate 導線は feature test で確認済み。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（candidateCount と enumerate の前提解決を共通化）
  adopted: yes
  classification: PR限定
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php, tests/Unit/ProgramRepetitionRuleSessionCandidateServiceTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`candidateCount()` と `enumerate()` が start/end 境界、week_of_month、start_time、daily/weekly の day_of_week 前提をそれぞれ個別に持っており、将来どちらか片方だけ修正されると「保存時や事前件数確認では通るが生成時に落ちる」ズレが起き得た。前提検証と cycle 分岐の正規化を `resolveCandidateRule()` へ集約し、件数計算と実列挙は従来どおり分離したまま維持した。あわせて unit test に `candidateCount() === enumerate()->count()` の日次/週次/0件ケースと、daily に `day_of_week` が入った不正設定の失敗系を追加して両公開経路の整合性を固定した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（破損既存ルール生成テストのデータ準備を withoutEvents 化）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`ProgramRepetitionRule` は saving イベントで schedule 制約を検証しているため、`invalid_effective_period` / `invalid_status` の feature test で通常の `update()` を使うと、将来モデル検証が強化された際に Arrange 段階で失敗して「壊れた既存データを生成アクションが利用者向けエラーへ変換する」という本来の契約を守れなくなる。該当 2 か所の不正データ仕込みだけを `ProgramRepetitionRule::withoutEvents(fn () => $rule->update(...))` へ置き換え、既存破損データ再現の意図を明示した。挙動確認は既存の generation feature test を再実行して行った。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（ProgramRepetitionRule CRUD テストの payload helper を遅延共有 fixture 化）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/AdminProgramRepetitionRuleCrudTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`validDailyPayload()` が呼び出しごとに Program / Location / Staff を新規作成していたため、同一テスト内の payload 準備で不要なモデル作成が増えていた。一方で `setUp()` で全テストへ無条件に 3 モデルを追加すると、payload helper を使わないケースまで fixture ノイズが増えるため、その提案は採用しなかった。代わりに `sharedProgram()` / `sharedLocation()` / `sharedStaff()` を追加し、必要時だけ初回生成して同一テスト内で再利用する形へ調整した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（ProgramRepetitionRule CRUD の未カバー認可経路を追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/AdminProgramRepetitionRuleCrudTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`program-repetition-rules` の CRUD ルートは `auth` + `can:access-admin` の admin group 配下にあり、index だけの認可テストでも現状のルート定義誤りはない。一方で将来 create/store/edit/update/destroy のいずれかが誤って group 外へ出た場合、index だけでは検知できないため、防御的な回帰として guest / non-admin 向けの未カバー経路を data provider で追加した。`show` は未定義、`generate` は別 feature test ですでに認可確認済みのため対象外とした。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（weekly→daily 更新時の stale day_of_week を update request で正規化）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Admin/UpdateProgramRepetitionRuleRequest.php, tests/Feature/Admin/AdminProgramRepetitionRuleCrudTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。編集フォームは `day_of_week` を常に送信し、既存 weekly ルールを daily へ切り替えた際に stale な曜日値が残るため、shared request の after validation で daily として弾かれていた。一方で shared `StoreProgramRepetitionRuleRequest` 全体で null 正規化すると、create 側の tampered daily payload まで黙って通してしまうため適用範囲が広すぎた。`UpdateProgramRepetitionRuleRequest` の `prepareForValidation()` だけで daily 時に `day_of_week=null` を上書きし、store 側の防御は維持したまま、実フォーム由来の weekly→daily 更新だけを通すように調整した。あわせて CRUD feature test を stale weekday 送信ケースへ更新し、修正前は失敗・修正後は成功することを確認した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（ProgramRepetitionRule CRUD の Update 側に week_of_month 改ざん拒否テストを追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/AdminProgramRepetitionRuleCrudTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`week_of_month` は shared `StoreProgramRepetitionRuleRequest` の `prohibited` ルールで store / update の両経路とも既に拒否されており、本番コードの欠落はなかった。一方で既存テストは store 側の tampered `week_of_month` しか確認しておらず、Update が別 Request クラスとして存在する構成では update 経路の回帰が見えにくかった。`test_update_rejects_tampered_week_of_month_and_leaves_rule_unchanged` を追加し、更新時に `week_of_month=2` を送っても validation error となり、既存ルールが不変であることを固定した。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（生成候補上限 366 件ちょうどの成功境界を回帰テストで固定）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/Admin/ProgramRepetitionRuleGenerationTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。既存の生成上限テストは「366 件を超える」失敗系だけで、上限文言どおりの「366 件ちょうどは許可される」境界が未固定だったため、`>` が `>=` に退行しても検知できなかった。daily ルールの有効期間を 2028-01-01〜2028-12-31 とした 366 件ちょうどの feature test を追加し、generate アクションが成功フラッシュを返し、`lesson_sessions` と `reservation_management` を各 366 件作成することを確認して fencepost バグを防ぐようにした。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（candidateCount 系メソッドの PHPDoc 粒度を公開契約に合わせて補強）
  adopted: yes
  classification: PR限定
  targets: app/Services/ProgramRepetitionRuleSessionCandidateService.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`candidateCount()` は `enumerate()` と並ぶ公開入口で同じ前提検証を共有しているため、前提条件・副作用なし・純粋性の説明粒度を揃える価値があった。一方で private helper の `countWeeklyCandidates()` は公開メソッドほど厚い契約までは不要だったため、正規化済み日付と `dayOfWeek` 0-6 を前提とし、状態変更なしで件数だけ返すことを簡潔に補足する最小差分に留めた。

- date: 2026-03-16
  branch: feat/ph6-2-admin-ui
  scope: PRレビュー指摘対応（Update request の daily 正規化責務を PHPDoc で明文化）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/Admin/UpdateProgramRepetitionRuleRequest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`UpdateProgramRepetitionRuleRequest::prepareForValidation()` は weekly→daily 切り替え時の stale `day_of_week` を shared validator 前に吸収する update 専用責務を持ち、これを store 側へ広げない設計意図も重要だった。責務・前提・更新方針を短い PHPDoc で追加し、daily 更新時だけ `day_of_week` を null 化し、store 経路は引き続き tampered daily payload を拒否する前提を明文化した。

- date: 2026-03-19
  branch: feat/ph8-3-1-alpine-data-standard
  scope: PRレビュー指摘対応（AppLayoutAlpineCspTest の brittle assertion を緩和）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/AppLayoutAlpineCspTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`document.addEventListener('alpine:init'` の完全一致は引用符形式に依存しており、イベント名自体を正規表現で検証する変更は妥当だったため採用した。一方で `<script defer src="..."></script>` の完全一致が属性順や `nonce` 追加に弱いという問題意識も妥当だが、単に `src` 文字列の存在だけを見る提案は `assertAssetLoadsBefore()` と重複して回帰検知力が落ちるため採用しなかった。代わりに、`src` を持つ `<script>` タグであることを属性順・追加属性・引用符差分に耐える正規表現で検証する最小差分へ調整した。

- date: 2026-03-19
  branch: feat/ph8-3-1-alpine-data-standard
  scope: PRレビュー指摘対応（empty x-data での capture group 判定不備を修正）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/AppLayoutAlpineCspTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`extractXDataAttributeValues()` は `PREG_SET_ORDER` の未参加 capture group が配列に存在しない前提を考慮せず、`$match[1] !== '' ? ... : $match[2] ...` と空文字比較で分岐していたため、`x-data=""` や `x-data=''` で未定義キー参照が発生していた。最小差分として「空文字かどうか」ではなく「最初に定義されている capture group を返す」実装へ変更し、空のダブルクォート・シングルクォート値が例外なく `false` 判定になる回帰ケースを provider に追加した。

- date: 2026-03-19
  branch: feat/ph8-3-1-alpine-data-standard
  scope: PRレビュー指摘対応（Blade コメントと pure echo の x-data 誤検知を抑止）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/AppLayoutAlpineCspTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`test_blade_views_do_not_inline_alpine_object_literals()` は raw Blade source を走査しており、`{{-- ... --}}` 内の `x-data="{ ... }"` と `x-data="{{ $componentName }}"` / `x-data="{!! $componentName !!}"` のような pure Blade echo 値まで `{...}` 判定に巻き込んで false positive になっていた。現行 `resources/views` に `x-data` は存在せず即時の実害はなかったが、将来のコメントアウトや動的登録名で不必要に赤くなるため、最小差分として Blade コメントを除去してから走査し、pure Blade echo 値だけは inline object literal 判定から除外するよう調整した。回帰ケースは provider に追加して再発を防止した。

- date: 2026-03-19
  branch: feat/ph8-4-1-common-ui-components
  scope: PRレビュー指摘対応（AppLayoutAlpineCspTest に applicationScript() lazy helper を追加）
  adopted: yes
  classification: PR限定
  targets: tests/Feature/AppLayoutAlpineCspTest.php, .cursor/review-feedback/log.md
  notes: 指摘は一部有効。`File::get(public_path('assets/js/app.js'))` が 4 テストで重複しており、読み込み箇所を 1 か所へ寄せる問題意識は妥当だった。一方で `setUp()` へ無条件に移すと `app.js` を使わないケースまでファイル読み込みに依存し、DataProvider ケースも含めて失敗面が広がるため、その提案は採用しなかった。代わりに `applicationScript()` の lazy helper を追加し、必要なテストだけが同一ロジック経由で `app.js` を取得する最小差分へ調整した。アサーション対象の文字列や検証観点は変更していない。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleIndexRequest の年月デフォルトを 1 回の Carbon::now と app.timezone に統一）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/ScheduleIndexRequest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`prepareForValidation()` で `Carbon::now()` を year / month 用に 2 回呼んでおり、境界瞬間に不整合が起こり得る。`config('app.timezone')` で `Carbon::now()` を 1 回だけ取得し、`ScheduleController::buildCalendarWeeks()` の `$timezone` と同じ基準で年月を補完するよう修正した。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（年範囲端で無効な前月・次月 URL を出さず null としビューで無効表示）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/ScheduleIndexRequest.php, app/Http/Controllers/ScheduleController.php, resources/views/pages/schedule/index.blade.php, tests/Feature/ScheduleSessionCalendarTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。2000-01 の前月・2100-12 の次月はバリデーション外の年になるため 422 になる URL を無条件生成していた。`MIN_YEAR` / `MAX_YEAR` を FormRequest に定数化し、Controller で前月・次月が範囲内のときだけ `route`、それ以外は null。Blade はリンクの代わりに disabled 風の span を表示。Feature テストで境界を追加。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleController の残席計算を remainingSeatsBreakdown に集約）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/ScheduleController.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`reserved_count` / `reserved_trial_count` からの残席が `totalRemainingSeats` と `serializeSessionRow` に重複していた。`remainingSeatsBreakdown()` で内訳と合計を一箇所で算出し、foreach では 1 枠あたり 1 回だけ呼び出して日次合計と行 JSON の両方に使うよう整理した。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleIndexRequest の failedValidation を expectsJson のときだけ JSON 422 に限定）
  adopted: yes
  classification: PR限定
  targets: app/Http/Requests/ScheduleIndexRequest.php, tests/Feature/ScheduleSessionCalendarTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。公開 HTML の不正クエリでも無条件 JSON 422 だとブラウザが生 JSON を表示する。`expectsJson()` が真のときだけ従来のカスタム JSON、それ以外は `parent::failedValidation` で `ValidationException`（リダイレクト＋セッションエラー）。TC-A-01/02/04/05 は `assertRedirect` と `assertSessionHasErrors`、TC-A-03 は `getJson` で JSON 本文を検証するよう更新した。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（ScheduleController の buildCalendarWeeks / serializeSessionRow に PHPDoc を追加）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/ScheduleController.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。`daySymbolForTotal` / `remainingSeatsBreakdown` と比べ週グリッド生成と view 行ペイロードの private helper に責務・前提・更新方針がなく粒度が分かれていた。週境界・`totalsByDay` の意味・Alpine/Blade との整合、および `remainingSeats` の受け渡し方を PHPDoc に明示した。

- date: 2026-03-22
  branch: feat/ph9-2-3-session-calendar
  scope: PRレビュー（buildCalendarWeeks の isToday を当月セルに限定）
  adopted: yes
  classification: PR限定
  targets: app/Http/Controllers/ScheduleController.php, tests/Feature/ScheduleSessionCalendarTest.php, .cursor/review-feedback/log.md
  notes: 指摘は有効。月外パディングセルで `ymd` / `day` は null なのに `isToday` だけ真になり得た。`isToday` に `$inMonth` を併用。PHPDoc に前提を追記し、`Carbon::setTestNow` で 4 月表示かつ当日 3/31 のときに `inMonth=false && isToday=true` が無いことを Feature テストで検証した。

- date: 2026-03-22
  branch: feat/ph10-3-1-member-settings
  scope: PH10-3-1 会員設定（パスワード・メール・カード・退会）新規実装
  adopted: no
  classification: none
  targets: （該当なし）
  notes: 外部PRレビュー指摘の採用はなし。実装時点でレビュー指摘の蓄積対象はなしと判定した。
