# Code Review（Laravel 12 / 脱Node/Vite）

未コミット差分に対して、**Laravelアプリ特有の観点**を含むセキュリティ/品質レビューを実施する。

## 前提
- 本プロジェクトは **脱Node/Vite**（ビルドなし）方針。`npm`/`vite` 前提の指摘や手順は出さない。
- テストは **PHPUnit（`php artisan test --compact`）** を前提とする。

## 手順

1. 変更ファイルを取得
- `git diff --name-only HEAD`

2. 変更ファイルごとにレビュー（特に `routes/`, `app/Http/`, `app/Models/`, `database/migrations/`, `resources/views/`）

### セキュリティ（CRITICAL）
- **認可漏れ（IDOR）**: show/edit/update/delete でオブジェクト所有者チェックがない（Policy/Gate/authorize不足）
- **入力バリデーション漏れ**: Form Request 未使用、`$request->all()` をそのまま利用
- **Mass Assignment**: `Model::create($request->all())` 等
- **SQL Injection**: 文字列連結の生SQL、`DB::select("...{$id}...")`
- **XSS（Blade）**: `{!! !!}` の不用意な使用、ユーザー入力のHTML出力
- **SSRF**: `Http::get($request->input('url'))` 等のユーザー指定URL fetch
- **アップロード**: 拡張子/サイズ/保存先/公開範囲の不備、ファイル名をユーザー入力で決める
- **CSRF**: POST/PUT/PATCH/DELETE フォームに `@csrf` がない
- **秘密情報の混入**: APIキー/トークン/Stripeキー等のハードコード、ログへの出力

### 品質（HIGH）
- **Controller肥大化**: 薄いController原則に反する（ビジネスロジックが詰まりすぎ）
- **例外/エラー処理の不足**: 404/403/409、決済失敗、満席、締切などの失敗系が握りつぶされている
- **DB整合性**: 予約定員/残高/サブスク枠などの不変条件がトランザクション/制約で守られていない
- **N+1**: relationship未eager load、テンプレでのループ内クエリ
- **デバッグ痕跡**: `dd()`, `dump()`, `ray()`, 過剰ログ
- **テスト不足**: 主要happy path + failure path + boundary が不足

### ベストプラクティス（MEDIUM）
- `env()` のアプリコード内使用（config以外）
- 日時/タイムゾーンの扱いが曖昧
- ページング未実装で一覧が肥大化
- ルート名/ビュー名の不整合（変更に追随できていない）

3. レポートを生成（優先度付き）
- Severity: CRITICAL / HIGH / MEDIUM / LOW
- File:Line（可能なら）
- Issue（何が危険/問題か）
- Fix（Laravel流の具体的な直し方）

4. 判定
- **CRITICAL または HIGH があれば commit/merge をブロック**（修正が先）

Never approve code with security vulnerabilities.
