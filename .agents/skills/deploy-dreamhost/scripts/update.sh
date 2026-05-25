#!/usr/bin/env bash
# Routine Blue Starfish update: push file changes and flush caches (no theme install/activate).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=config.sh
source "${SCRIPT_DIR}/config.sh"

REMOTE_WP="cd ${REMOTE_DIR} &&"
VERIFY_URL="${VERIFY_URL:-https://www.bluestarfishguesthouse.com/}"
VERIFY_GREP="${VERIFY_GREP:-Blue Starfish}"

usage() {
  cat <<EOF
Usage: $(basename "$0") [options]

  Push local changes to DreamHost and flush WordPress caches. For first-time
  setup or theme activation, use deploy.sh instead.

Options:
  --pull            sync-down from server (mirror; deletes extra local files)
  --files-only      rsync only; skip WP-CLI cache flush
  --cache-only      flush caches on server; skip rsync
  --skip-verify     skip homepage curl check
  -h, --help        show this help

Environment:
  REMOTE_HOST, REMOTE_DIR  (see config.sh)
  VERIFY_URL, VERIFY_GREP  post-update smoke test
EOF
}

DO_PULL=0
FILES_ONLY=0
CACHE_ONLY=0
SKIP_VERIFY=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --pull) DO_PULL=1 ;;
    --files-only) FILES_ONLY=1 ;;
    --cache-only) CACHE_ONLY=1 ;;
    --skip-verify) SKIP_VERIFY=1 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
  esac
  shift
done

remote_wp() {
  ssh "${REMOTE_HOST}" "${REMOTE_WP} $*"
}

flush_caches() {
  echo "=== WordPress: flush caches ==="
  remote_wp "wp rewrite flush"
  remote_wp "wp cache flush" || true
  remote_wp "wp eval 'if ( function_exists( \"wp_cache_clear_cache\" ) ) { wp_cache_clear_cache(); echo \"wp-super-cache cleared\\n\"; }'" || true
}

verify_homepage() {
  echo "=== Verify ${VERIFY_URL} ==="
  html=$(curl -sL --max-time 30 "${VERIFY_URL}" || true)
  if echo "${html}" | grep -q "${VERIFY_GREP}"; then
    echo "OK: homepage contains \"${VERIFY_GREP}\""
  else
    echo "WARN: homepage did not match \"${VERIFY_GREP}\" — hard-refresh the browser" >&2
    exit 1
  fi
}

echo "=== Blue Starfish update ==="

if [[ "${DO_PULL}" -eq 1 ]]; then
  echo "=== rsync down (mirror) ==="
  "${SCRIPT_DIR}/sync-down.sh"
  echo "=== Done (pull only) ==="
  exit 0
fi

if [[ "${CACHE_ONLY}" -eq 0 ]]; then
  echo "=== rsync up ==="
  "${SCRIPT_DIR}/sync-up.sh"
fi

if [[ "${FILES_ONLY}" -eq 0 ]]; then
  flush_caches
fi

if [[ "${SKIP_VERIFY}" -eq 0 && "${DO_PULL}" -eq 0 ]]; then
  verify_homepage
fi

echo "=== Done ==="
if [[ "${FILES_ONLY}" -eq 0 && "${CACHE_ONLY}" -eq 0 ]]; then
  remote_wp "wp option get stylesheet" || true
fi
