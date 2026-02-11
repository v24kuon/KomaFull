# KomaFull MVP ドメインイベント一覧（概念）

本ドキュメントは、KomaFull MVP における「状態遷移の起点」を言語化した **概念上のイベント一覧** です。  
実装では必ずしもイベントバスを導入せず、Service/Job/Controller 内の処理単位として扱って構いません（ただし **ログ/監査/再実行** の単位は本一覧に合わせます）。

> 重要: 決済・予約確定・付与・返金の真実は **Stripe Webhook** です。return_url は真実にしません。

---

## 1. 体験（Trial）

### TrialFlowStarted

- **起点**: 体験申込フォームが送信され、仮会員（ログイン可能）と `trial_applications` が作成された
- **責務**:
  - `trial_applications.status=pending_payment`（カードの場合）または `payment_method=onsite` を確定
  - `stripe_checkout_session_id`（カードの場合）を保存（`unique`）
- **備考**: メール認証は `users.email_verified_at` を真実とする

### TrialReservedOnsite

- **起点**: 体験（現地）で予約が確定した
- **責務（同一TX）**:
  - `reservation_management` 行ロック + 体験枠空き確認
  - `reservations` 作成（`seat_bucket=trial`, `payment_method=trial_onsite`）
  - `trial_applications.status=reserved`, `reservation_id` セット

---

## 2. Stripe Webhook（受信 → 処理）

### StripeWebhookReceived

- **起点**: Stripe Webhook の受信（署名検証済み）
- **真実**: `Stripe-Signature` が正しいこと
- **責務**:
  - `webhook_logs` を作成（`event_id` unique）
  - Job を enqueue（実処理は非同期）

### StripeWebhookClaimed

- **起点**: Job が `webhook_logs.status=received` を `processing` に atomic 更新できた
- **責務**:
  - 並行実行の物理ガード（0行更新なら処理せず終了）

### StripeCheckoutSucceeded (Trial Card)

- **起点**: Webhookにより「体験カード決済の成功」を確認できた
- **責務（同一TX）**:
  - `trial_applications`（外部ID）をロック
  - `reservation_management` をロックし空き確認
  - 空きあり: 予約確定（`trial_card`）・カウンタ更新・`trial_applications.status=reserved`
  - 満席: `trial_applications.status=refund_pending`（予約は作らない）

### StripeCheckoutSucceeded (Prepaid)

- **起点**: Webhookにより「プリペイド決済の成功」を確認できた
- **責務（同一TX）**:
  - `prepaid_purchases` をロック
  - `balance_transactions` に付与を1回だけ記録（`idempotency_key` unique）
  - `prepaid_purchases.status=completed`

### StripeRefundSucceeded / StripeRefundFailed

- **起点**: 返金Jobが成功/失敗した
- **責務**:
  - 成功: `trial_applications.status=refunded`, `refunded_at` セット
  - 失敗: `trial_applications.status=refund_failed`（管理画面で回収）
- **備考**: Stripe API 呼び出しは `idempotency_key` を固定生成し、二重返金を防ぐ

---

## 3. 通常予約（Normal）

### ReservationConfirmedNormal

- **起点**: 本会員が予約を確定した
- **責務（同一TX）**:
  - `reservation_management` ロック + 一般枠空き確認
  - `payment_method` に応じて消費処理（ロック順序固定）
    - `subscription`: `course_entitlements`（→items）をロックし `used_uses` 更新
    - `tickets/points`: `member_profiles` をロックし `balance_transactions` を記録
  - `reservations` 作成（`seat_bucket=normal`）

### ReservationCanceled

- **起点**: キャンセル確定
- **責務（同一TX）**:
  - `reservation_management` ロック
  - `reservations` ロック（重複キャンセル防止）
  - カウンタ減算 + 消費の巻き戻し（`balance_transactions` / `course_entitlements`）

---

## 4. 出欠・会員化

### AttendanceMarkedAttended

- **起点**: 管理画面等で `attendances.attendance_status=attended` が確定した
- **責務**:
  - 対応する会員を本会員へ昇格（`member_profiles.member_status=active`）
  - 監査メモを残す（誰がいつ操作したか）

### MemberPromotedToActive

- **起点**: 会員が `active` に昇格した
- **責務**:
  - **昇格通知（After-Commit）**: お祝いと利用案内メールを送信
  - 監査証跡の確定

---

## 5. 運用救済（Admin Recovery）

### AdminManualRetryRequested

- **起点**: 管理画面で「手動リトライ」を実行
- **責務**:
  - 対象 `webhook_logs` / `trial_applications` / `prepaid_purchases` を再処理キューへ投入
  - 監査メモ（`resolution_notes` 等）必須

