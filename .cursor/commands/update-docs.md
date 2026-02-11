# Update Documentation（Laravel / 脱Node）

Laravelアプリのドキュメントを、**コード/設定（composer + artisan + .env.example）**を唯一の真実として同期する。

## 前提
- 本プロジェクトは **脱Node/Vite**。`package.json` / `npm scripts` を前提にしない。
- ドキュメント生成/更新は、ユーザーが明示的に依頼した場合にのみ行う（このコマンドはその前提で使用）。

## 手順
1. `composer.json` の scripts / 依存関係を確認
   - `composer install`, `composer run <script>` の説明表を生成
   - 必要なら `composer.lock` も参照（実際のバージョン）

2. `.env.example` を読み、環境変数一覧を作る
   - 目的、必須/任意、形式（例: `STRIPE_KEY`, `STRIPE_SECRET`）を記述
   - **秘密情報の値そのものは書かない**

3. `docs/CONTRIB.md` を生成/更新（推奨）
   - 開発フロー（セットアップ/サーバ起動）
   - 主要コマンド（composer/artisan）
   - フォーマット（Pint）
   - テスト（PHPUnit: `php artisan test --compact`）

4. `docs/RUNBOOK.md` を生成/更新（推奨）
   - デプロイ手順（環境ごとの前提を明示）
   - ロールバック（直前のリリースへ戻す/設定キャッシュの扱い）
   - よくある障害（DB/migration、APP_KEY、権限、キャッシュ）

5. 陳腐化したドキュメントを列挙
   - `docs/` 配下で 90日以上更新されていないファイルをリストアップし、手動レビュー対象として提示

6. 差分サマリを提示（変更ファイル、変更点の要約）

## 置き換えコマンド例（README/CONTRIB向け）
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

php artisan test --compact
vendor/bin/pint --dirty
```

## Single source of truth
- `composer.json` / `composer.lock`
- `.env.example`
- `php artisan about`（環境情報の参照として）
