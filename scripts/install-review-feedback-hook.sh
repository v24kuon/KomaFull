#!/usr/bin/env bash
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
source_hook="$repo_root/.githooks/pre-commit"

if [[ ! -f "$source_hook" ]]; then
  echo "[install-review-feedback-hook] source hook not found: $source_hook"
  exit 1
fi

chmod +x "$source_hook"
git -C "$repo_root" config core.hooksPath .githooks

echo "[install-review-feedback-hook] enabled core.hooksPath=.githooks"
echo "[install-review-feedback-hook] hook executable: $source_hook"

