# コミットのみ（現在のブランチ）

## 概要

現在のブランチに対して、ローカルの変更をコミットだけ行うためのシンプルなコマンドです。
リモートへのプッシュやブランチ戦略（main 直コミット禁止など）は扱わず、**コミットメッセージ規約に沿ったコミット**だけを行います。

## 前提条件

- 変更済みファイルが存在すること
- コミットメッセージの具体的な書き方は、`.cursor/rules/commit-message-format.mdc` などで定義された規約に従うこと

## 実行手順（対話なし）

1. 未コミット差分を確認し、コミットメッセージの内容を検討する（例：`git diff` や `git diff --cached`）
2. （推奨）品質チェック（Laravel / 脱Node前提）
   - 変更にPHPが含まれる場合: `vendor/bin/pint --dirty`
   - 変更に影響するテストを実行: `php artisan test --compact`（最小スコープ→必要なら全体）
3. 変更のステージング（`git add -A`）
4. コミット（環境変数または引数でメッセージを渡す）

### A) 安全な一括実行（メッセージ引数版）

```bash
MSG="<Prefix>: <サマリ（命令形/簡潔に）>" \
vendor/bin/pint --dirty && \
php artisan test --compact && \
git add -A && \
git commit -m "$MSG"
```

例：

```bash
MSG="fix: 不要なデバッグログ出力を削除" \
vendor/bin/pint --dirty && \
php artisan test --compact && \
git add -A && git commit -m "$MSG"
```

### B) ステップ実行（読みやすさ重視）

```bash
# 1) 差分を確認
git status
git diff

# 2) （推奨）品質チェック
vendor/bin/pint --dirty
php artisan test --compact

# 3) 変更をステージング
git add -A

# 4) コミット（メッセージを編集）
git commit -m "<Prefix>: <サマリ（命令形/簡潔に）>"
```

## ノート

- コミットメッセージのフォーマットやメッセージ生成の原則は、`.cursor/rules/commit-message-format.mdc` のルールに従ってください。
- 本プロジェクトは **脱Node/Vite** 方針のため、`npm`/`vite` を前提としたチェックはここに入れません。
- ブランチ戦略（例：main 直コミット禁止、作業用ブランチ運用）やリモートへのプッシュ (`git push`) は、このコマンドの対象外です。必要に応じて、プロジェクトごとの README / CONTRIBUTING / 別コマンドで定義してください。
