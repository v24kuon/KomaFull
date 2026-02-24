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
tracked_targets=()

while IFS= read -r file; do
  [[ -z "$file" ]] && continue

  case "$file" in
    app/*|tests/*|database/*|routes/*|config/*|bootstrap/*|resources/*)
      requires_tracking_update=1
      tracked_targets+=("$file")
      ;;
    .cursor/review-feedback/log.md)
      has_log_update=1
      ;;
  esac
done <<< "$staged_files"

if [[ "$requires_tracking_update" -eq 0 ]]; then
  exit 0
fi

if [[ "$has_log_update" -eq 0 ]]; then
  REPO_ROOT="$(git rev-parse --show-toplevel)"
  LOG_PATH="$REPO_ROOT/.cursor/review-feedback/log.md"

  if [[ ! -f "$LOG_PATH" ]]; then
    echo "[review-feedback-guard] ERROR: .cursor/review-feedback/log.md が見つかりません。"
    exit 1
  fi

  TODAY="$(date +%F)"
  BRANCH_NAME="$(git branch --show-current)"

  TARGETS=""
  for target in "${tracked_targets[@]}"; do
    if [[ -z "$TARGETS" ]]; then
      TARGETS="$target"
    else
      TARGETS="$TARGETS, $target"
    fi
  done

  {
    echo
    echo "- date: $TODAY"
    echo "  branch: ${BRANCH_NAME:-unknown-branch}"
    echo "  scope: 自動記録（コミット時にlog.md未更新）"
    echo "  adopted: no"
    echo "  classification: none"
    echo "  targets: ${TARGETS:-unknown-target}"
    echo "  notes: pre-commit が自動で追記。必要に応じて内容を編集してください。"
  } >> "$LOG_PATH"

  git add ".cursor/review-feedback/log.md"
  echo "[review-feedback-guard] INFO: log.md 未更新のためテンプレート行を自動追記しました。"
fi

# Phase 1+2: Validate log content and RFP rules (staged log.md content)
REPO_ROOT="$(git rev-parse --show-toplevel)"
set -o pipefail
if ! git show ":.cursor/review-feedback/log.md" 2>/dev/null | php "$REPO_ROOT/scripts/review-feedback-validate.php" --log-path=-; then
  echo "[review-feedback-guard] Log validation or RFP check failed. Fix the errors above and retry."
  exit 1
fi

