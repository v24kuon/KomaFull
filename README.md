# KomaFull（booking2）

レッスン予約を中心に、体験申込・本会員化、回数券／ポイント／サブスク（コース）による決済と枠付与を扱う **MVP** 向けの Laravel アプリケーションです。

## システム概要

| 区分 | 内容 |
|------|------|
| **フロント** | **No-Build**（Node/Vite 不使用）。`public/assets/` の静的ファイル、`v_asset()` によるキャッシュバスティング。UI は **Bootstrap 5** と **Alpine.js（CSP 互換）**、必要に応じて **HTMX**。 |
| **認証** | **Laravel Fortify**（メール認証あり）。会員・管理者は同一 `users` テーブルで、`role`（`member` / `admin`）により区別。 |
| **決済** | **Laravel Cashier（Stripe）**。Checkout 経由の体験・プリペイド、サブスク請求（`invoice.payment_succeeded`）など。ビジネス上の正は **Webhook**（成功画面のリダイレクトのみに依存しない）。 |
| **公開サイト** | プログラム・開催枠・店舗・お問い合わせなど。 |
| **会員（マイページ）** | ダッシュボード、プロフィール、会員設定（パスワード／メール、Stripe Billing Portal 経由のカード管理、サブスクのプラン変更・請求期間末解約、退会 等）。 |
| **管理画面** | `/admin` 配下。マスタ（カテゴリ、プログラム、繰り返しルール、店舗設定など）とセッション生成など（Gate: `access-admin`）。 |

データモデルの一覧は `docs/database-schema.md` を参照してください。

---

## 開発環境のセットアップ（開発者向け）

前提: PHP 8.5 系、Composer。

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

`.env` の最低限の例（ローカル）:

- `APP_URL` … ブラウザでアクセスする URL（Stripe のリダイレクトや Webhook 検証と整合させる）
- `DB_*` … 利用するデータベース（`.env.example` は SQLite 例）
- `QUEUE_CONNECTION` … Webhook 処理でジョブを使うため、本番・検証では **database / redis 等の永続キュー**を推奨

開発サーバー:

```bash
composer run dev
# または
php artisan serve
```

### フロントエンドについて

ビルドコマンド（`npm run build` 等）は **使いません**。CSS/JS の変更は `public/assets/` を直接編集し、リリース時は `ASSET_VERSION` を更新してください（`.env.example` 参照）。

### Git / コミット

本リポジトリは共有の pre-commit でレビュー指摘ログを要求します。clone 後に一度:

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit
# または
bash scripts/install-review-feedback-hook.sh
```

`app/`・`tests/` 等を含むコミットでは `.cursor/review-feedback/log.md` の更新が必要です（詳細はフック内メッセージとプロジェクトルール参照）。

---

## 管理者向け：はじめにやること

### 1. 管理者ユーザーの用意

管理者は `users.role = 'admin'` のユーザーです。登録フローは会員と同じ Fortify 経由でもよいですが、**初回のみ** DB や Tinker で `role` を `admin` に更新する運用が簡単です。

例（本番では適切なメール・パスワードに変更すること）:

```bash
php artisan tinker
>>> $u = \App\Models\User::where('email', 'admin@example.com')->first();
>>> $u->role = 'admin';
>>> $u->save();
```

ログイン後、**`/admin`** にアクセスできることを確認します（会員向けマイページのナビからも管理画面へ遷移可能）。

### 2. Stripe の準備（必須）

1. [Stripe Dashboard](https://dashboard.stripe.com/) でアカウントを用意する。
2. **API キー**（公開可能キー / シークレット）を `.env` に設定する。

   - `STRIPE_KEY`
   - `STRIPE_SECRET`

3. **Webhook エンドポイント**を登録する。Laravel Cashier の既定パスは次のとおりです。

   - URL: `https://<あなたのドメイン>/stripe/webhook`
   - ローカル検証には [Stripe CLI](https://stripe.com/docs/stripe-cli) の `stripe listen --forward-to localhost:8000/stripe/webhook` などが便利です。

4. Webhook 用の **署名シークレット**を `.env` に設定する。

   - `STRIPE_WEBHOOK_SECRET`（Dashboard でエンドポイント作成後に表示される `whsec_...`、または CLI 起動時の値）

5. 本アプリの `config/cashier.php` では、既定イベントに加え **`checkout.session.completed`**、**`checkout.session.async_payment_succeeded`**、**`invoice.payment_succeeded`** を扱います。Stripe 側の Webhook 購読に、これらが含まれるようにしてください。

6. **通貨・表示**は `CASHIER_CURRENCY` / `CASHIER_CURRENCY_LOCALE`（`.env.example` 参照）で調整します。

### 3. キュー（Webhook を確実に処理する）

Stripe Webhook 受信後、アプリはジョブをキューに積みます。**ワーカーが動いていないと決済・付与処理が進みません。**

```bash
php artisan queue:work
```

本番では Supervisor 等で常駐させてください。`QUEUE_CONNECTION=database` の場合は `jobs` テーブルが使われます。

### 4. マスタと Stripe Price の対応（サブスク・プラン変更）

- サブスクプランは DB の **`course_plans`** と、Stripe の **Price ID** を紐づけます（`stripe_price_id`）。
- 会員の「プラン変更」画面は、**ステータスが有効**で **`stripe_price_id` が入っている**プランのみを候補として表示します。管理画面や運用フローで、Stripe で作成した Price ID をマスタに反映してください。

### 5. Stripe Customer Portal（会員のカード管理）

会員設定の「カード情報を管理」は **Stripe Billing Portal** にリダイレクトします。Stripe Dashboard の **Customer portal** で、利用可能な機能（支払い方法の更新など）を有効にしてください。アプリ側では `MemberStripeBillingPortalService` が Cashier 経由でセッションを作成します。

### 6. その他の環境変数（参考）

- `MAIL_*` … メール送信（会員登録確認・問い合わせなど）。本番では実際のメーラーを設定。
- `MAIL_CONTACT_TO` … お問い合わせフォームの宛先（未設定時は `config` の既定）。
- `ASSET_VERSION` … 静的ファイル更新時に変更し、ブラウザキャッシュを更新。

---

## テスト

```bash
php artisan test --compact
```

---

## ライセンス

アプリケーションロゴ等を除き、元リポジトリに準じます。利用フレームワーク（Laravel 等）のライセンスは各公式ドキュメントを参照してください。
