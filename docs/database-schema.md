# データベーススキーマ（テーブル/カラム一覧）

このドキュメントは **KomaFull MVP** の「最終的なテーブル名/カラム名」の一覧です（実装時の迷いを減らすためのソース）。

## 共通ルール

- **主キー**: 原則 `id`（BIGINT）
- **公開用識別子**: CSVの `MB.../R.../SS...` 等は、各テーブルの `code`（`unique`）として保持
- **タイムスタンプ**: 原則 `created_at` / `updated_at`
- **体験枠**: `trial_*`（例: `trial_capacity`, `reserved_trial_count`）で統一（CSVの `exp_*` は名称変更）

---

## 管理者（admin）の扱い

- **同一 `users` テーブル**: 会員と管理者を同じ `users` で管理する。`role`（例: `member` / `admin`）または `is_admin`（boolean）で区別する。
- **認証**: 会員も管理者も同じ Fortify + `users` でログインする。
- **認可（実装方針）**:
  - **Gates**: モデルに紐づかない操作（例: 管理ダッシュボード表示）は Gate で判定（例: `$user->isAdmin` で許可）。
  - **Policy::before()**: 「管理者はすべての操作を許可」にする場合は、各 Policy の `before()` で `$user->isAdministrator()` が true なら `return true`。
  - **Gate::before()**: アプリ全体で「管理者は全 Gate を通過」にしたい場合は、`AppServiceProvider::boot()` で `Gate::before(fn ($user, $ability) => $user->isAdministrator() ? true : null)` を登録する（`null` で通常の Gate/Policy に委譲）。
- **ルート保護**: `/admin` 配下は `auth` ミドルウェアに加え、管理者であることを確認するミドルウェア（例: `role:admin` や `is_admin` をチェック）で保護する。

---

## 認証/基盤（Laravel）

### `users`

- `id`
- `name`
- `email`
- `email_verified_at`
- `password`
- `remember_token`
- `role`（例: `member`, `admin`）※管理者は同じテーブルで区別。`is_admin`（boolean）で代替しても可。
- `created_at`
- `updated_at`
- （Cashier導入後）`stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`

### `password_reset_tokens`

- `email`（PK）
- `token`
- `created_at`

### `sessions`

- `id`（PK）
- `user_id`
- `ip_address`
- `user_agent`
- `payload`
- `last_activity`

### `cache`

- `key`（PK）
- `value`
- `expiration`

### `cache_locks`

- `key`（PK）
- `owner`
- `expiration`

### `jobs`

- `id`
- `queue`
- `payload`
- `attempts`
- `reserved_at`
- `available_at`
- `created_at`

### `job_batches`

- `id`（PK）
- `name`
- `total_jobs`
- `pending_jobs`
- `failed_jobs`
- `failed_job_ids`
- `options`
- `cancelled_at`
- `created_at`
- `finished_at`

### `failed_jobs`

- `id`
- `uuid`
- `connection`
- `queue`
- `payload`
- `exception`
- `failed_at`

---

## 会員/プロフィール

### `member_profiles`（`users` と 1:1）

- `id`
- `user_id`（`unique`）
- `code`（公開用: `MB...`）
- `member_status`（例: `provisional`, `active`, `withdrawn`）
- `tel`
- `birth_date`
- `activated_at`（本会員化日時）
- `withdrawn_at`
- `created_at`
- `updated_at`

### `additional_items`（追加項目マスタ）

- `id`
- `code`（公開用: `AI...`）
- `additional_item_type`（例: `member_profile`）
- `label_name`
- `input_type`（例: `text`, `checkbox`, `radio`, `select`）
- `digits`（任意）
- `status`
- `created_at`
- `updated_at`

### `member_additional_item_values`（会員×追加項目の値）

- `id`
- `member_profile_id`
- `additional_item_id`
- `value`（文字列/JSONを想定）
- `created_at`
- `updated_at`

---

## マスタ（レッスン/開催）

### `categories`

- `id`
- `code`（公開用）
- `name`
- `sort_order`
- `status`
- `created_at`
- `updated_at`

### `program_types`

- `id`
- `code`（公開用）
- `name`
- `sort_order`
- `status`
- `created_at`
- `updated_at`

### `programs`

- `id`
- `code`（公開用: `PR...`）
- `category_id`
- `program_type_id`
- `name`
- `level`
- `duration_minutes`
- `overview`
- `detail`
- `price`
- `point_cost`
- `ticket_cost`
- `status`
- `created_at`
- `updated_at`

### `locations`

- `id`
- `code`（公開用: `L...`）
- `name`
- `address`
- `tel`
- `email`
- `description`
- `status`
- `created_at`
- `updated_at`

### `location_images`

- `id`
- `location_id`
- `path`
- `image_type`（例: `main`, `sub1`, `sub2`）
- `sort_order`
- `created_at`
- `updated_at`

### `staffs`

- `id`
- `code`（公開用: `SF...`）
- `name`
- `gender`
- `age`
- `licence_skill`
- `main_expertise`
- `role`
- `description`
- `status`
- `created_at`
- `updated_at`

### `staff_images`

- `id`
- `staff_id`
- `path`
- `image_type`（例: `main`, `sub1`, `sub2`）
- `sort_order`
- `created_at`
- `updated_at`

### `lesson_sessions`

- `id`
- `code`（公開用: `SS...`）
- `program_id`
- `location_id`
- `staff_id`
- `starts_at`
- `capacity`（通常枠）
- `trial_capacity`（体験枠）
- `status`
- `created_at`
- `updated_at`

### `program_repetition_rules`

- `id`
- `program_id`
- `location_id`
- `staff_id`
- `cycle_type`（例: `daily`, `weekly`, `monthly`）
- `day_of_week`（週次）
- `week_of_month`（月次）
- `start_date`
- `end_date`
- `start_time`
- `capacity`
- `trial_capacity`
- `status`
- `created_at`
- `updated_at`

---

## 予約（コア）

### `reservation_management`（定員カウンタキャッシュ）

- `id`
- `lesson_session_id`（`unique`）
- `reserved_count`（通常の確定予約数）
- `reserved_trial_count`（体験の確定予約数）
- `created_at`
- `updated_at`

### `trial_applications`（体験の申込/決済の進行状態）

- `id`
- `user_id`
- `lesson_session_id`
- `payment_method`（`card` / `onsite`）
- `status`（例: `pending_payment`, `processing`, `reserved`, `expired`, `canceled`, `refund_pending`, `refunded`, `refund_failed`）
  - `processing`: Webhook受信後、処理中の状態（重複ガード用）
  - `refund_pending`: 満席等の理由で自動返金待ちの状態
  - `refund_failed`: 自動返金処理が失敗した状態（管理者対応が必要）
- `stripe_checkout_session_id`（`unique`, nullable）
- `expires_at`（nullable）
- `reservation_id`（nullable, 確定時に `reservations` を紐付け）
- `refunded_at`（nullable）
- `refund_reason`（nullable）
- `canceled_at`（nullable）
- `created_at`
- `updated_at`

### `reservations`（確定予約のみ）

- `id`
- `code`（公開用: `R...`）
- `user_id`
- `lesson_session_id`
- `seat_bucket`（`trial` / `normal`）
- `payment_method`（例: `trial_card`, `trial_onsite`, `subscription`, `tickets`, `points`）
- `status`（例: `confirmed`, `canceled`）
- `ticket_cost`（監査用スナップショット）
- `point_cost`（監査用スナップショット）
- `course_entitlement_id`（nullable, `payment_method=subscription` の場合）
- `canceled_at`（nullable）
- `cancel_reason`（nullable）
- `created_at`
- `updated_at`

### `attendances`

- `id`
- `reservation_id`（`unique` 推奨）
- `attendance_status`（例: `attended`, `absent`）
- `marked_at`
- `marked_by_staff_id`（nullable）
- `created_at`
- `updated_at`

---

## プリペイド（回数券/ポイント）

### `prepaid_products`

- `id`
- `code`（公開用: `PM...`）
- `prepaid_type`（例: `tickets`, `points`）
- `sales_name`
- `usage_count`（付与量）
- `expires_in_days`（有効日数）
- `price`
- `status`
- `created_at`
- `updated_at`

### `prepaid_purchases`

- `id`
- `code`（公開用: `PP...`）
- `user_id`
- `prepaid_product_id`
- `purchased_at`
- `expires_at`
- `status`（例: `pending_payment`, `processing`, `completed`, `expired`, `grant_failed`）
  - `processing`: Webhook受信後、付与処理中の状態（重複ガード用）
  - `grant_failed`: 決済成功したが、ポイント/チケットの付与に失敗した状態（管理者対応が必要）
- `stripe_checkout_session_id`（`unique`, nullable）
- `created_at`
- `updated_at`

### `balance_transactions`（台帳）

- `id`
- `user_id`
- `unit`（`tickets` / `points`）
- `amount`（+付与 / -消費）
- `transaction_type`（例: `grant`, `consume`, `refund`, `expire`, `adjust`）
- `idempotency_key`（`unique`, 重複計上防止キー）
- `prepaid_purchase_id`（nullable, 付与元の追跡が必要な場合）
- `reservation_id`（nullable, 消費/戻しの紐付け）
- `stripe_reference_id`（nullable, 決済側IDの保存用）
- `occurred_at`
- `expires_at`（nullable）
- `created_at`
- `updated_at`

### `webhook_logs`（Webhook受信ログ）

- `id`
- `event_id`（Stripeの `evt_...` ID。`unique`。冪等性担保用）
- `provider`（例: `stripe`）
- `payload`（JSON: 受信した生データ全体）
- `status`（例: `received`, `processed`, `failed`）
- `error_message`（TEXT, nullable: 失敗時の例外内容）
- `processed_at`（nullable: 処理完了日時）
- `resolved_at`（nullable: 手動解決日時。リカバリUI用）
- `resolution_notes`（TEXT, nullable: 手動解決時のメモ）
- `created_at`
- `updated_at`

※ 全てのWebhook受信を、処理の成否に関わらず記録し、障害調査と監査証跡として使用する。

---

## サブスク（コース）

### `course_plans`

- `id`
- `code`（公開用: `CR...`）
- `name`
- `stripe_price_id`
- `usage_count`（周期あたり付与回数）
- `allocation_type`（`total` / `per_category`）
  - `total`: 合計回数方式（`course_entitlement_items` は使わない）
  - `per_category`: カテゴリ別回数方式（`course_entitlement_items` を使う）
- `level`
- `description`
- `status`
- `created_at`
- `updated_at`

### `course_plan_categories`

- `id`
- `course_plan_id`
- `category_id`
- `created_at`
- `updated_at`

### `course_entitlements`（周期枠）

- `id`
- `user_id`
- `course_plan_id`
- `period_start`
- `period_end`
- `granted_uses`
- `used_uses`
- `created_at`
- `updated_at`

### `course_entitlement_items`（任意：カテゴリ別枠にする場合）

- `id`
- `course_entitlement_id`
- `category_id`
- `granted_uses`
- `used_uses`
- `created_at`
- `updated_at`

---

## 設定/通知

### `store_settings`（基本1行）

- `id`
- `program_label`
- `session_label`
- `staff_label`
- `location_label`
- `reserve_deadline_minutes`
- `cancel_deadline_minutes`
- `withdrawal_deadline_days`
- `created_at`
- `updated_at`

### `mail_templates`

- `id`
- `key`（例: `verified`, `registered`, `trial_reserved`, `paid`, `reserved`, `canceled`, `withdrawn`）
- `sender_name`
- `sender_email`
- `subject`
- `body`
- `status`
- `created_at`
- `updated_at`

---

## Cashier（Stripe）が追加するテーブル（導入後）

※ Cashierのマイグレーションにより自動追加されます。ここでは「存在すること」だけを前提にします。

- `subscriptions`
- `subscription_items`
