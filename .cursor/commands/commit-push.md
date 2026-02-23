# コミット＆プッシュ（現在のブランチ）

## 概要

現在のブランチに対して変更をコミットし、リモートへプッシュするための汎用的なコマンド例です。
main/master への直接プッシュ禁止や、コミット前に実行する品質チェック（lint / test / build など）は、各プロジェクトのポリシーに応じてこのテンプレートを調整してください。

## 前提条件

- 変更済みファイルが存在すること
- リモート `origin` が設定済みであること

## 実行手順（対話なし）

1. ブランチ確認（main/master 直プッシュ防止）
2. 変更のステージング（`git add -A`）
3. レビュー指摘の蓄積ガードを実行（必須）
   - `bash scripts/review-feedback-guard.sh`
4. 必要に応じて品質チェック（Laravel / 脱Node前提）を実行
   - 変更にPHPが含まれる場合: `vendor/bin/pint --dirty`
   - テスト: `php artisan test --compact`（最小スコープ→必要なら全体）
5. コミット（引数または環境変数のメッセージ使用）
6. プッシュ（`git push -u origin <current-branch>`）

## 使い方

### A) 安全な一括実行（メッセージ引数版）

```bash
MSG="<Prefix>: <サマリ（命令形/簡潔に）>" \
BRANCH=$(git branch --show-current) && \
if [ "$BRANCH" = "main" ] || [ "$BRANCH" = "master" ]; then \
  echo "⚠️ main/master への直接プッシュは禁止です"; exit 1; \
fi

# 変更をステージング
git add -A || exit 1

# 任意の品質チェック（必要な場合のみ / Laravel）
bash scripts/review-feedback-guard.sh || exit 1
# vendor/bin/pint --dirty || exit 1
# php artisan test --compact || exit 1

git commit -m "$MSG" && \
git push -u origin "$BRANCH"
```

例：

```bash
MSG="fix: 不要なデバッグログ出力を削除" \
BRANCH=$(git branch --show-current) && \
if [ "$BRANCH" = "main" ] || [ "$BRANCH" = "master" ]; then \
  echo "⚠️ main/master への直接プッシュは禁止です"; exit 1; \
fi

# 変更をステージング
git add -A || exit 1

# 任意の品質チェック（必要な場合のみ）
bash scripts/review-feedback-guard.sh || exit 1
# ./scripts/quality-check.sh || exit 1

git commit -m "$MSG" && git push -u origin "$BRANCH"
```

### B) ステップ実行（読みやすさ重視）

```bash
# 1) ブランチ確認
BRANCH=$(git branch --show-current)
if [ "$BRANCH" = "main" ] || [ "$BRANCH" = "master" ]; then
  echo "⚠️ main/master への直接プッシュは禁止です"; exit 1;
fi

# 2) 変更をステージング
git add -A

# 3) レビュー指摘の蓄積ガード（必須）
bash scripts/review-feedback-guard.sh

# 4) 任意のローカル品質チェック（必要に応じて追加）
# 例:
# echo "品質チェック実行中..."
# vendor/bin/pint --dirty || exit 1
# php artisan test --compact || exit 1

# 5) コミット（メッセージを編集）
git commit -m "<Prefix>: <サマリ（命令形/簡潔に）>"

# 6) プッシュ
git push -u origin "$BRANCH"
```

## ノート

- コミットメッセージのフォーマットやメッセージ生成の原則は、`.cursor/rules/commit-message-format.mdc` などの規約に従ってください。
- 先に `git status` や `git diff` で差分を確認してからの実行を推奨します。
- レビュー指摘の蓄積漏れ防止のため、**コード変更（`app/` `tests/` `database/` `routes/` `config/` `bootstrap/`）を含むコミットでは** `.cursor/review-feedback/log.md` の同時更新を必須とするガードを必ず通してください（ドキュメントのみの変更コミットは対象外）。
- `git commit --no-verify` でのガード回避は禁止です。
- Git Hook を使う場合は `.githooks/pre-commit` を利用します（有効化はチーム運用に従って実施）。
- 本プロジェクトは **脱Node/Vite** 方針のため、`npm`/`vite` を前提としたチェックはこのテンプレートに含めません。