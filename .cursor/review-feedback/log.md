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
