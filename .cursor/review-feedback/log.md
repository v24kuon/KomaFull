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
