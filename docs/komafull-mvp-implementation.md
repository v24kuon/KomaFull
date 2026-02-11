# KomaFull MVP 実装詳細（No-Build / Fortify / Cashier/Stripe）

本ドキュメントは、Cursor plan「`komafull_mvp_plan_25de4b13`」を一次入力として、実装者が迷わない粒度まで情報を補完した **実装詳細（仕様 + 運用 + テスト方針）** です。

> 重要: **DBスキーマ（テーブル/カラム/型/制約）の一次ソースは `docs/database-schema.md`** です。迷ったら必ずこちらを優先してください。

---

## 目次

- [1. スコープ（MVPでやること/やらないこと）](#1-スコープmvpでやることやらないこと)
- [2. 用語（ユビキタス言語）](#2-用語ユビキタス言語)
- [3. 不変条件（Invariants）](#3-不変条件invariants)
- [4. 技術方針（No-Build UI / セキュリティ）](#4-技術方針no-build-ui--セキュリティ)
- [5. データモデル概要（主要テーブル/状態）](#5-データモデル概要主要テーブル状態)
- [6. ユーザーフロー（体験/通常/購買/会員化）](#6-ユーザーフロー体験通常購買会員化)
- [7. Stripe Webhook（署名検証・Atomic Claim・冪等性）](#7-stripe-webhook署名検証atomic-claim冪等性)
- [8. トランザクション/ロック戦略（定員・消費・返金）](#8-トランザクションロック戦略定員消費返金)
- [9. 管理（運用UI・監査・Runbook）](#9-管理運用ui監査runbook)
- [10. メール通知（タイミング・トリガー）](#10-メール通知タイミングトリガー)
- [11. テスト方針（失敗系優先）](#11-テスト方針失敗系優先)
- [付録A: 状態遷移（体験/プリペイド/Webhookログ）](#付録a-状態遷移体験プリペイドwebhookログ)
- [付録B: 外部ID/冪等キー設計](#付録b-外部id冪等キー設計)
- [付録C: ドメインイベント一覧](#付録c-ドメインイベント一覧)

---

## 1. スコープ（MVPでやること/やらないこと）

### MVPでやること

- **体験予約（カード/現地）**
  - 体験申込 → **メール認証** → **仮会員作成（ログイン可）**
  - セッション選択 → 支払い方法選択
    - **カード**: Stripe Checkout → **Webhookを正**として決済成功を確認して体験予約確定
    - **現地**: 選択時点で体験予約確定
  - **方針（重要）**: カード決済中は枠を確保しない。決済成功後に満席なら **自動返金**（予約は作らない）
- **本会員化（体験→本会員）**
  - 体験予約完了＝仮会員
  - **昇格トリガー**: 体験予約の出欠が `attended`（出席）になった瞬間、本会員（`active`）へ昇格
- **通常予約（本会員のみ）**
  - 予約時に消費元を選択: **サブスク枠 / 回数券 / ポイント**
- **プリペイド購入（回数券/ポイント）**
  - Stripe決済 → Webhookで確定 → 台帳（`balance_transactions`）へ付与
- **サブスク（コース）**
  - 課金周期ごとに枠を付与、対象カテゴリを受講可能
  - **繰越なし（期末失効）**
- **管理（最小）**
  - マスタ管理（カテゴリ/種別/プログラム/コース/プリペイド/店舗設定/ロケーション/スタッフ）
  - セッション生成（手動 + 繰り返しルールから自動生成）
  - 返金/付与失敗/Webhook失敗の検知と救済（手動リトライ + 監査メモ）

### MVPでやらないこと（明文化）

- **決済成功リダイレクトを“予約確定”の真実にしない**（Webhookが正）
- **「予約成立後の返金」を標準機能としては未確定**（方針決定後にイベント/台帳整合を定義して追加）
- **Stripe Elements を用いたカード入力UI**は原則採用しない（CSP/No-Build衝突リスクが高い）
  - カード管理は **Stripe Customer Portal（推奨）** を第一候補とする

---

## 2. 用語（ユビキタス言語）

- **開催枠（Session）**: `lesson_sessions`（Laravel標準の `sessions` と衝突しない）
- **定員カウンタ**: `reservation_management`（確定予約数のカウンタキャッシュ。行ロックの起点）
- **体験枠/一般枠**:
  - 体験枠: `lesson_sessions.trial_capacity` と `reservation_management.reserved_trial_count`
  - 一般枠: `lesson_sessions.capacity` と `reservation_management.reserved_count`
- **確定予約**: `reservations`（成立した予約のみ。決済待ち/離脱は入れない）
- **体験申込（未確定含む）**: `trial_applications`（決済待ち/返金待ち/失敗の吸収）
- **台帳（Ledger）**: `balance_transactions`（付与/消費/返金/失効/調整の全履歴）
- **Webhook受信ログ**: `webhook_logs`（Stripe Eventの監査証跡 + 冪等ガード）

---

## 3. 不変条件（Invariants）

### 真実はどこか（Single Source of Truth）

- **予約の一次ソースは `reservations`**
  - `reservations` には **成立した予約のみ** を入れる（決済待ち/離脱/期限切れ/返金は入れない）
- **体験の未完了・返金など「予約にならない状態」は `trial_applications`**
  - `trial_applications.reservation_id` は **確定したら埋まる**（未確定は `NULL`）
- **メール認証の状態は `users.email_verified_at`**
  - `trial_applications.status` に `email_pending` のような認証状態は持たない
- **決済成功リダイレクトは正にしない**
  - 予約確定/残高付与/サブスク付与は **Webhookを正**にする
- **定員の整合性は `reservation_management` 行ロックで担保する**
  - “空き確認→予約作成→カウンタ更新”は同一トランザクション + `lockForUpdate()` で完結させる

---

## 4. 技術方針（No-Build UI / セキュリティ）

### 4.1 No-Build（必須）

規約の一次ソースは `.cursor/rules/no-build-convention.mdc` です（要点のみ抜粋）。

- 静的資産は **`public/assets/`** 配下に集約
- **`@vite` 全面禁止**
- `v_asset()` でURL生成し、`ASSET_VERSION` によるキャッシュバスティング（`?v=YYYYMMDD_N`）
- インライン `<script>` / `<style>` 原則禁止
- CDN利用は **バージョン固定 + SRI必須**（SRIが無い場合は自己ホスト）

### 4.2 UIスタックの責務分離

- **HTMX**: フォーム送信/部分更新/検索/ページング等「サーバー状態が正」のUI
- **Alpine.js**: トグル/モーダル等「表示状態」だけ
  - `x-data` 直書き禁止。外部JSで `Alpine.data()` 登録方式を徹底
  - HTMX差し替え領域内にAlpine状態を持たない

### 4.3 セキュリティ（必須）

#### Webhook署名検証

- Stripe Webhookは **`Stripe-Signature` を必ず検証**する
- 署名不正は 400 で拒否し、**副作用処理（予約/付与/返金）を絶対に進めない**
  - 監査のために「拒否した事実」はアプリログ等に **最小限の情報**で記録（DoS/PII対策）

#### 認証/認可

- 認証: Fortify（メール認証あり）
- 認可: `/admin` 配下は `auth` に加えて管理者チェック（`role=admin` など）で保護
  - 「会員/管理者が同一 `users`」のため、**管理機能の権限漏れ**は致命傷。Policy/Gate適用点を実装とテストで固定する

#### CSP/SRI

- CSPは **Report-Only → Enforce** の2段階で導入
- 原則: `script-src 'self'`（`'unsafe-inline'` / `'unsafe-eval'` は許可しない）
- Stripe要件との整合:
  - 本MVPは **Stripe Checkout / Customer Portal（リダイレクト型）** を優先し、アプリ内で `Stripe.js/Elements` を原則使わない
  - 将来 `Stripe.js` が必要になった場合のみ、最小限で許可（例: `script-src 'self' https://js.stripe.com`、必要に応じて `frame-src/connect-src`）

#### ログ/PII

- `webhook_logs.payload` は監査に有用だが、PIIが混入し得る
  - **閲覧権限を管理者に限定**
  - 検索/表示時のマスキング方針を決める（メール/住所等）
  - 保持期間（例: 180日）を運用で定義（MVPでは最低「追跡できる期間」を確保）

### 4.4 環境変数/設定（最小）

> 値は **ドキュメントに記載しない**（シークレット保護）。名称は導入時にCashier/Fortifyの公式に合わせる。

- **No-Build**
  - `ASSET_VERSION`: `v_asset()` のキャッシュバスティング版数（`?v=YYYYMMDD_N`）
- **Stripe / Cashier**
  - `STRIPE_KEY`: Stripe Publishable key（Elements採用時のみブラウザで利用）
  - `STRIPE_SECRET`: Stripe Secret key（サーバー側）
  - `STRIPE_WEBHOOK_SECRET`: Stripe Webhook署名検証用（最優先）
  - `CASHIER_CURRENCY`（必要に応じて）: 通貨

### 4.5 ディレクトリ/責務（推奨）

- **静的資産**: `public/assets/` 配下に集約
  - `public/assets/css/`（`app.css`, `pages/*.css`）
  - `public/assets/js/`（`app.js`）
  - `public/assets/img/`
  - `public/assets/vendor/`（Bootstrap/HTMX/Alpine など、自己ホストする外部ライブラリ）
- **Bladeレイアウト**:
  - `layouts/app.blade.php` で `v_asset()` を用いて一括読み込み
  - ページ固有は `@stack('styles')` / `@stack('scripts')` で管理
- **Laravel 12**:
  - ミドルウェア登録は `bootstrap/app.php` 側（`withMiddleware()`）に集約される点に注意

---

## 5. データモデル概要（主要テーブル/状態）

> 具体的なカラムは `docs/database-schema.md` を参照。

### 主要テーブル（目的）

- `users`: 会員/管理者（同一テーブル）
- `member_profiles`: 会員プロフィール（`users` と1:1、`member_status` を保持）
- `categories`, `program_types`, `programs`: マスタ
- `lesson_sessions`: 開催枠
- `reservation_management`: 定員カウンタ（`lesson_session_id` が `unique`）
- `trial_applications`: 体験申込/決済/返金の進行状態
- `reservations`: 確定予約のみ（`seat_bucket`, `payment_method` を必ず保存）
- `attendances`: 出欠（`attended` が本会員化のトリガー）
- `prepaid_products`, `prepaid_purchases`: プリペイド商品/購入
- `balance_transactions`: 台帳（付与/消費/返金/失効/調整）
- `webhook_logs`: Webhook受信ログ（冪等/監査/リトライ）
- `course_plans`, `course_entitlements`, `course_entitlement_items`: サブスク枠

### 状態（例）

- `trial_applications.status`（例）: `pending_payment`, `processing`, `reserved`, `refund_pending`, `refunded`, `refund_failed`, `expired`, `canceled`
- `prepaid_purchases.status`（例）: `pending_payment`, `processing`, `completed`, `grant_failed`, `expired`
- `webhook_logs.status`（推奨）: `received` → `processing` → `processed` / `failed`
  - `docs/database-schema.md` の例（`received/processed/failed`）をベースに、**Atomic Claimのため `processing` を追加**する

### 重要なユニーク制約（事故防止）

- `webhook_logs.event_id`: **unique**（第一冪等ガード）
- `trial_applications.stripe_checkout_session_id`: **unique**（第二冪等ガード）
- `prepaid_purchases.stripe_checkout_session_id`: **unique**（第二冪等ガード）
- `balance_transactions.idempotency_key`: **unique**（台帳二重計上防止）

---

## 6. ユーザーフロー（体験/通常/購買/会員化）

### 6.1 体験（現地）

1. 体験申込フォーム送信
2. メール認証（`users.email_verified_at`）
3. `trial_applications` 作成（`payment_method=onsite`）
4. **同一トランザクションで**:
   - `reservation_management` を `lockForUpdate()`
   - 体験枠空き確認（`trial_capacity` と `reserved_trial_count`）
   - `reservations` 作成（`seat_bucket=trial`, `payment_method=trial_onsite`, `status=confirmed`）
   - `reserved_trial_count` インクリメント
   - `trial_applications.status=reserved`、`reservation_id` セット

### 6.2 体験（カード: Stripe Checkout）

1. 体験申込フォーム送信 → メール認証 → 仮会員作成
2. `trial_applications` 作成（`status=pending_payment`）
3. サーバー側で Stripe Checkout Session を作成し、`stripe_checkout_session_id` を保存（`unique`）
4. ユーザーをStripeへリダイレクト
5. Stripe復帰後の画面は **「枠確定中（Webhook待ち）」** として表示
6. Webhook受信（署名検証）→ `webhook_logs` 記録 → Job処理へ
7. Job内で **決済成功を確認**し、同一トランザクションで:
   - `trial_applications` ロック（外部IDで特定）
   - `reservation_management` ロック
   - 空きがあれば `reservations` 作成 + カウンタ更新 + `trial_applications.status=reserved`
   - 満席なら **予約を作らず** `trial_applications.status=refund_pending` へ更新し、返金Jobへ
8. 返金Job成功: `refunded` / 失敗: `refund_failed`

> UX要件: 「決済完了＝予約確定」と誤認させない。待機画面・マイページ・メール文面の一貫した表現が必須。

### 6.3 本会員化（仮会員 → active）

- トリガー: `attendances.attendance_status=attended`
- 実装方針:
  - 管理画面の出席チェックで状態が `attended` へ更新された瞬間に、`member_profiles.member_status` を `active` に更新
  - 例外対応: 管理者が手動で昇格/取り消し可能（監査メモ必須）

### 6.4 通常予約（本会員のみ）

- 予約時に消費元をユーザーが選択（`payment_method` に保存し監査可能にする）
  - `subscription` / `tickets` / `points`
- 予約確定処理は、定員更新を含めて **単一トランザクション + 行ロック** で完結させる

### 6.5 プリペイド購入（回数券/ポイント: Stripe Checkout）

1. 商品選択（`prepaid_products`）
2. `prepaid_purchases` 作成（`status=pending_payment`）
3. Stripe Checkout Session 作成、`stripe_checkout_session_id` を保存（`unique`）
4. Stripe復帰後の画面は **「付与確定中（Webhook待ち）」** を表示
5. Webhook処理（署名検証 + Atomic Claim）
6. **同一トランザクションで**:
   - `prepaid_purchases` ロック（外部IDで特定）
   - `balance_transactions` に付与を1回だけ記録（`idempotency_key` unique）
   - `prepaid_purchases.status=completed`
7. 付与失敗時は `grant_failed` へ遷移し、管理画面で回収可能にする

### 6.6 サブスク（購読/枠付与/消費/失効）

- **購読**:
  - Cashierで購読を作成し、`course_plans.stripe_price_id` と紐付ける
- **枠付与（周期ごと）**:
  - 周期ごとに `course_entitlements` を生成し `granted_uses` を付与
  - `allocation_type=per_category` の場合は `course_entitlement_items` を生成
- **消費（通常予約の支払い方法=subscription）**:
  - 予約確定TX内で `course_entitlements`（→items）をロックし `used_uses` を増やす
- **失効（繰越なし）**:
  - `period_end` 到来で余りは失効（繰越なしを仕様として固定する）

### 6.7 キャンセル（巻き戻し）

- **締切**: `store_settings.cancel_deadline_minutes` を真実として判定（UIに表示）
- **同一トランザクションで**:
  - `reservation_management` ロック
  - `reservations` ロック（二重キャンセル防止）
  - `seat_bucket` に応じてカウンタ減算
  - `payment_method` に応じて消費の巻き戻し（台帳 or サブスク枠）

---

## 7. Stripe Webhook（署名検証・Atomic Claim・冪等性）

### 7.1 “真実はWebhook”の原則

- return_url 到達は「画面遷移が成功した」だけであり、**決済や付与の真実ではない**
- 予約確定/付与/返金は必ず Webhook 処理で確定させる

### 7.2 受信フロー（推奨アーキテクチャ）

1. Webhook受信（Controller）
2. **署名検証（必須）**
3. `webhook_logs` を作成（`status=received`、`event_id` unique）
4. **即 200** を返却（Stripeの再送を抑制）
5. 実処理は Queue Job に委譲（リトライ/バックオフ）

> 推奨: `webhook_logs` の作成と Job enqueue は「署名検証に成功したもののみ」。署名不正はDBにpayloadを保存せず、アプリログへ最小限の情報（到達時刻/リクエストID等）を記録する。

### 7.3 Atomic Claim（並行実行の物理ガード）

Job冒頭で、以下を満たす「原子的更新」を実施する。

- `webhook_logs.status=received` の行だけを `processing` に更新する
- 更新が 1 行なら「このワーカーが処理権を獲得」
- 0 行なら「別ワーカーが既に処理開始/完了」なので **即終了**

これにより、重複配送・手動リトライ・タイムアウト再投入が重なっても **同一イベントが並行実行されない**。

### 7.4 冪等性（必須の二重ガード）

- **第一ガード**: `webhook_logs.event_id` を `unique`
- **第二ガード**: 外部IDを `unique`（例: `trial_applications.stripe_checkout_session_id`）
- **副作用API（返金など）**は、Stripeの `idempotency_key` を必ず指定

### 7.5 失敗時の扱い（運用とセットで必須）

- Jobは `$tries` / `backoff` を持ち、ロック競合など一時エラーは自動回復させる
- 全リトライ枯渇で `webhook_logs.status=failed`（`error_message` 保存）
- 管理画面から **再実行**（手動リトライ）できること（再実行しても二重処理にならない＝冪等であること）

---

## 8. トランザクション/ロック戦略（定員・消費・返金）

### 8.1 基本原則

- 定員/消費に関わる更新は **単一トランザクション** で完結させる
- `reservation_management` を `lockForUpdate()` してから空き確認・更新を行う
- **ロック順序を固定**し、デッドロック確率を下げる

### 8.2 絶対ロック順序（Single Source of Truth）

ユースケースごとにロック対象は異なるが、取得する場合は必ず次の順序で行う。

1. `trial_applications` / `prepaid_purchases`（外部IDで特定するもの）
2. `reservation_management`（定員カウンタ）
3. `reservations`（キャンセル等、既存予約を更新する場合）
4. `member_profiles`（回数券/ポイントの消費/巻き戻しを伴う場合）
5. `course_entitlements`
6. `course_entitlement_items`

> 必須運用: `reservation_management` 行はセッション作成時に必ず作り、ロック対象を保証する。

### 8.3 返金の境界（TX内/外）

- **DB整合（予約を作らない/状態を `refund_pending` にする）**はTX内
- **Stripe返金API呼び出し（外部副作用）**はTX外（Job）で行う
  - リトライが起きても二重返金しないよう、`idempotency_key` を固定生成する

### 8.4 典型処理パターン（擬似コード）

> 実装の理解を揃えるための概要。実コードは `app/Services` と Job に分割し、DBクエリをControllerに置かない。

**体験カードWebhook（決済成功）**

```text
// 署名検証済み -> webhook_logs(received)作成 -> Jobへ
Job:
  if !atomicClaim(webhook_log): return
  tx:
    lock trial_application by stripe_checkout_session_id
    lock reservation_management by lesson_session_id
    if trialCapacityFull:
      set trial_application refund_pending
      set webhook_log processed
      commit
      dispatch refund job (idempotency_key fixed)
      return
    create reservation(trial_card)
    increment reserved_trial_count
    set trial_application reserved + reservation_id
    set webhook_log processed
```

**通常予約（tickets/points）**

```text
tx:
  lock reservation_management
  if capacityFull: abort
  lock member_profile
  create reservation(normal, payment_method=tickets/points)
  insert balance_transactions (consume) with unique idempotency_key
  increment reserved_count
```

---

## 9. 管理（運用UI・監査・Runbook）

### 9.1 管理画面（MVP必須）

- **緊急リカバリダッシュボード**
  - `trial_applications.status=refund_failed`
  - `prepaid_purchases.status=grant_failed`
  - `webhook_logs.status=failed`
  - 「手動リトライ」「強制解決」「監査メモ」を提供
- Webhookログ閲覧（外部ID/イベントIDで検索可能に）
- 出席管理（`attended` への更新 + 本会員昇格）

### 9.2 Runbook（不整合時の復旧手順）

最低限、以下を文章化して運用できるようにする。

- `refund_failed`:
  - 対象の `trial_application` と `stripe_checkout_session_id` を特定
  - Stripeダッシュボードで支払い/返金状態を確認
  - 管理画面から返金Jobを手動リトライ（冪等キー固定）
  - 解決したら `resolution_notes` を残す
- `grant_failed`:
  - 決済成功を確認 → 台帳付与を手動実行（監査メモ必須）
- `webhook_logs.status=failed`:
  - `error_message` と payload で原因特定
  - 修正後に「再実行」
- “枠確定中”が長時間滞留:
  - Webhook未到達/Job停止/Stripe障害を疑い、管理画面から突合/再実行

### 9.3 監視/アラート（最低限）

- `webhook_logs.status=failed` 件数（増加は即アラート）
- `trial_applications.status=refund_failed` 件数
- `prepaid_purchases.status=grant_failed` 件数
- 「枠確定中（Webhook待ち）」「付与確定中（Webhook待ち）」の滞留（一定時間超で要調査）

---

## 10. メール通知（タイミング・トリガー）

### 10.1 基本原則

- **After-Commit 原則**: 全てのメール送信は、DBトランザクションが **正常にコミットされた直後（afterCommit）** に実行する。ロールバック時に誤送信してはならない。
- **Webhook 正の原則**: カード決済関連（予約確定・付与・返金）の通知は、**Stripe Webhook 処理完了** をトリガーとする。`return_url` 到達（画面復帰）を起点にした送信は厳禁。
- **PII（個人情報）最小化**: メール本文には住所・電話番号・決済詳細・Stripe生データ等を載せない。予約コード・日時・店舗名・問い合わせ先など最小限に留め、詳細はログイン後のマイページで提示する。
- **二重送信防止**: Webhookの再送やJobの再試行によりメールが重複しないよう、送信済みフラグやユニークキーを用いて **冪等性を担保** する。

### 10.2 通知タイミング一覧

| 対象イベント | トリガー（真実/起点） | 送信タイミング（厳密） | Recipient |
|---|---|---|---|
| ユーザー登録 | `users` レコード作成 | 登録TXコミット直後 | ユーザー |
| メール認証完了 | `email_verified_at` セット | 更新TXコミット直後 | ユーザー |
| 体験予約（現地） | `reservations` 作成 | 確定TXコミット直後 | ユーザー |
| 体験予約（カード） | Webhookによる予約確定 | Webhook Jobコミット直後 | ユーザー |
| 自動返金開始 | `status=refund_pending` 遷移 | 更新TXコミット直後 | ユーザー |
| 自動返金完了 | `status=refunded` 確定 | 完了TXコミット直後 | ユーザー |
| 自動返金失敗 | `status=refund_failed` 確定 | 失敗TXコミット直後 | ユーザー + 管理者 |
| 通常予約（本会員） | `reservations` 作成 | 確定TXコミット直後 | ユーザー |
| キャンセル | 予約キャンセル + 巻き戻し | 完了TXコミット直後 | ユーザー |
| プリペイド購入成功 | Webhookによる付与完了 | 完了TXコミット直後 | ユーザー |
| プリペイド付与失敗 | `status=grant_failed` 確定 | 失敗TXコミット直後 | ユーザー + 管理者 |
| サブスク枠付与 | `course_entitlements` 作成 | 作成TXコミット直後 | ユーザー |
| 本会員昇格 | `member_status=active` 遷移 | 更新TXコミット直後 | ユーザー |

### 10.3 代表的な通知内容

| 通知種類 | 主な内容 |
|---|---|
| 予約確定系 | 予約コード、開催日時、プログラム名、店舗場所、支払い方法、キャンセル締切 |
| 自動返金系 | 予約不成立の旨、返金開始/完了の案内、返金反映の目安、代替セッション案内 |
| 失敗・障害系 | お詫び、運営が手動対応する旨、問い合わせ用照合ID |
| 昇格・付与系 | 昇格お祝い、付与量、有効期限、残高確認用マイページURL |

---

## 11. テスト方針（失敗系優先）

実装時は `.cursor/rules/test-strategy.mdc` に従い、正常系と同数以上の失敗系を必ず含める。

### テスト観点（例）

| Case ID | Input / Precondition | Perspective (Equivalence / Boundary) | Expected Result | Notes |
|---|---|---|---|---|
| TC-WH-01 | 署名不正 | Boundary – invalid | 400で拒否、DB副作用なし | 署名検証が最初のゲート |
| TC-WH-02 | 同一event_idが重複配送 | Equivalence – duplicate | 1回だけ処理される | Atomic Claim + unique |
| TC-WH-03 | 決済成功後に満席 | Boundary – concurrency | 予約は1件のみ、もう1件は返金へ | レース条件 |
| TC-WH-04 | 返金API障害 | Failure – external | `refund_failed` へ遷移し運用で回収可 | 不当利得防止 |
| TC-AUTH-01 | 会員が/adminへアクセス | Failure – authz | 403 | 権限漏れ防止 |

---

## 付録A: 状態遷移（体験/プリペイド/Webhookログ）

### trial_applications.status（例）

`pending_payment` → `processing` → `reserved`

満席:

`processing` → `refund_pending` → `refunded` / `refund_failed`

### prepaid_purchases.status（例）

`pending_payment` → `processing` → `completed`（付与成功） / `grant_failed`

### webhook_logs.status（推奨）

`received` → `processing` → `processed` / `failed`

---

## 付録B: 外部ID/冪等キー設計

### 外部ID（保存/unique推奨）

- `webhook_logs.event_id`（Stripe `evt_...`）: **unique**
- `trial_applications.stripe_checkout_session_id`: **unique**
- `prepaid_purchases.stripe_checkout_session_id`: **unique**

### Stripe idempotency_key（例）

- 返金: `refund:trial_application:{trial_application_id}`
- 付与（台帳）: `balance:grant:prepaid_purchase:{prepaid_purchase_id}`

> 重要: idempotency_key は「同じ操作」なら常に同じになるよう固定生成する。

---

## 付録C: ドメインイベント一覧

`docs/domain-events.md` を参照。  
※ 本ドキュメント作成時に `docs/domain-events.md` を新規作成して補完済み。

