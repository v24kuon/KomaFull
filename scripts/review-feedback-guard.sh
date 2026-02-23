#!/usr/bin/env bash
set -euo pipefail

if [[ "${SKIP_REVIEW_FEEDBACK_GUARD:-0}" == "1" ]]; then
  echo "[review-feedback-guard] skipped by SKIP_REVIEW_FEEDBACK_GUARD=1"
  exit 0
fi

staged_files="$(git diff --cached --name-only --diff-filter=ACMRTUXB)"

if [[ -z "$staged_files" ]]; then
  exit 0
fi

requires_tracking_update=0
has_log_update=0

while IFS= read -r file; do
  [[ -z "$file" ]] && continue

  case "$file" in
    app/*|tests/*|database/*|routes/*|config/*|bootstrap/*)
      requires_tracking_update=1
      ;;
  esac

  case "$file" in
    .cursor/review-feedback/log.md)
      has_log_update=1
      ;;
  esac
done <<< "$staged_files"

if [[ "$requires_tracking_update" -eq 0 ]]; then
  exit 0
fi

if [[ "$has_log_update" -eq 1 ]]; then
  exit 0
fi

cat <<'EOF'
[review-feedback-guard] ERROR:
コード変更（app/tests/database/routes/config/bootstrap）が含まれていますが、
レビュー指摘ログ `.cursor/review-feedback/log.md` の更新がステージされていません。

同一コミットに必ず次を含めてください:
  - .cursor/review-feedback/log.md

その後、再度コミットを実行してください。
EOF

exit 1

