#!/usr/bin/env bash
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
source_hook="$repo_root/.githooks/pre-commit"
target_hook="$repo_root/.git/hooks/pre-commit"

if [[ ! -f "$source_hook" ]]; then
  echo "[install-review-feedback-hook] source hook not found: $source_hook"
  exit 1
fi

cp "$source_hook" "$target_hook"
chmod +x "$target_hook"

echo "[install-review-feedback-hook] installed: $target_hook"

