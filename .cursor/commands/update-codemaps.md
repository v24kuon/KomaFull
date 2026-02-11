# Update Codemaps（Laravel / 脱Node）

Laravelアプリの実態（routes/controllers/requests/models/migrations/views/jobs）から、**コードマップ（CODEMAPS）**を更新する。

## 前提
- 本プロジェクトは **脱Node/Vite**。TypeScript/Nodeベースの解析は行わない。
- CODEMAPは **高レベル構造**に絞り、実装詳細の羅列は避ける。

## 手順
1. リポジトリ構造をスキャン（主に以下）
   - `routes/`（`web.php`, `console.php`）
   - `bootstrap/app.php`（Laravel 12 のmiddleware/例外/ルーティング設定）
   - `app/Http/Controllers/`
   - `app/Http/Requests/`
   - `app/Models/`
   - `app/Jobs/`, `app/Events/`, `app/Notifications/`
   - `database/migrations/`
   - `resources/views/`（Blade + components）
   - `composer.json`, `composer.lock`

2. ルート表面の把握（可能なら）
   - `php artisan route:list` の結果から、主要な named route と middleware を整理

3. CODEMAP出力（推奨）
   - `docs/CODEMAPS/INDEX.md`
   - `docs/CODEMAPS/routes.md`
   - `docs/CODEMAPS/http.md`（controllers/requests/policies）
   - `docs/CODEMAPS/database.md`（migrations）
   - `docs/CODEMAPS/views.md`
   - `docs/CODEMAPS/jobs.md`
   - `docs/CODEMAPS/integrations.md`

4. 差分の大きさを計算
   - 既存のCODEMAPと比較し、変更率を算出（概算でOK）
   - **変更率が30%を超える場合は、更新前にユーザー承認を要求**

5. 各CODEMAPに freshness timestamp（更新日）を入れる

6. レポートを保存
   - `.reports/codemap-diff.txt` に差分サマリ（変更率、主な変更点）を出す

## チェック観点
- ルート名/URL/Controller が実態と一致しているか
- 認可（Policy/Gate/middleware）をマップに明示できているか
- DBは migrations を唯一の真実として整理できているか（sqlite互換の注意点含む）
- “脱Node/Vite” の方針と矛盾する手順が混ざっていないか
