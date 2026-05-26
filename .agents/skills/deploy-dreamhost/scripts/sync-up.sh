#!/usr/bin/env bash
# Push local repo files to DreamHost (does not delete remote-only files).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=config.sh
source "${SCRIPT_DIR}/config.sh"

REMOTE="${REMOTE_HOST}:${REMOTE_DIR}/"

echo "Syncing up: ${REPO_ROOT}/ -> ${REMOTE}"

rsync -avz --no-times --omit-dir-times \
  --exclude '.dh-diag/' \
  "${RSYNC_EXCLUDES[@]}" \
  "${REPO_ROOT}/" \
  "${REMOTE}"

echo "Done."
