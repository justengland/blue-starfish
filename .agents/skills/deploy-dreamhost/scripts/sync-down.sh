#!/usr/bin/env bash
# Pull site files from DreamHost into the local repo (mirror).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=config.sh
source "${SCRIPT_DIR}/config.sh"

REMOTE="${REMOTE_HOST}:${REMOTE_DIR}/"

echo "Syncing down: ${REMOTE} -> ${REPO_ROOT}/"
echo "(mirror with --delete; see skill deploy-dreamhost)"

rsync -avz --delete \
  "${RSYNC_EXCLUDES[@]}" \
  "${REMOTE}" \
  "${REPO_ROOT}/"

echo "Done."
