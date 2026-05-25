#!/usr/bin/env bash
# Full Blue Starfish deploy: ensure Ocean Breeze theme, rsync up, activate, flush caches, verify.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=config.sh
source "${SCRIPT_DIR}/config.sh"

THEME_SLUG="ocean-breeze"
THEME_DIR="${REPO_ROOT}/wp-content/themes/${THEME_SLUG}"
ZIP="${REPO_ROOT}/ocean-breeze.zip"
REMOTE_WP="cd ${REMOTE_DIR} &&"

SITE_NAME="${SITE_NAME:-Blue Starfish Guest Houses in the Corpus Christi bay area.}"
SITE_TAGLINE="${SITE_TAGLINE:-Corpus Christi mid-term guesthouse rentals.}"
VERIFY_URL="${VERIFY_URL:-https://www.bluestarfishguesthouse.com/}"
VERIFY_GREP="${VERIFY_GREP:-Blue Starfish}"

usage() {
  cat <<EOF
Usage: $(basename "$0") [options]

  Push repo to DreamHost, activate Ocean Breeze, flush caches, verify homepage.

Options:
  --skip-sync       Skip rsync (files already on server)
  --skip-activate   Skip wp theme activate (already active)
  --skip-verify     Skip curl homepage check
  --install-zip     Force unzip ocean-breeze.zip into wp-content/themes/
  -h, --help        Show this help

Environment:
  REMOTE_HOST, REMOTE_DIR  (see config.sh)
  SITE_NAME, SITE_TAGLINE  WordPress blogname / blogdescription
  VERIFY_URL, VERIFY_GREP  Post-deploy smoke test
EOF
}

SKIP_SYNC=0
SKIP_ACTIVATE=0
SKIP_VERIFY=0
FORCE_ZIP=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-sync) SKIP_SYNC=1 ;;
    --skip-activate) SKIP_ACTIVATE=1 ;;
    --skip-verify) SKIP_VERIFY=1 ;;
    --install-zip) FORCE_ZIP=1 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
  esac
  shift
done

ensure_theme() {
  if [[ -f "${THEME_DIR}/style.css" && "${FORCE_ZIP}" -eq 0 ]]; then
    echo "Theme present: ${THEME_DIR}"
    return
  fi
  if [[ ! -f "${ZIP}" ]]; then
    echo "Missing theme dir and ${ZIP}" >&2
    exit 1
  fi
  echo "Installing theme from ${ZIP}..."
  unzip -o "${ZIP}" -d "${REPO_ROOT}/wp-content/themes/"
}

remote_wp() {
  ssh "${REMOTE_HOST}" "${REMOTE_WP} $*"
}

echo "=== Blue Starfish deploy ==="
ensure_theme

if [[ "${SKIP_SYNC}" -eq 0 ]]; then
  echo "=== rsync up ==="
  "${SCRIPT_DIR}/sync-up.sh"
else
  echo "=== rsync skipped ==="
fi

if [[ "${SKIP_ACTIVATE}" -eq 0 ]]; then
  echo "=== WordPress: activate ${THEME_SLUG} ==="
  remote_wp "wp theme activate ${THEME_SLUG}"
fi

echo "=== WordPress: site title ==="
remote_wp "wp option update blogname $(printf '%q' "${SITE_NAME}")"
remote_wp "wp option update blogdescription $(printf '%q' "${SITE_TAGLINE}")"

echo "=== WordPress: clear file cache ==="
ssh bluestarfish "cd bluestarfishguesthouse.com && rm -rf wp-content/cache/*" 2>&1

echo "=== WordPress: permalinks + cache ==="
remote_wp "wp rewrite flush"
remote_wp "wp cache flush" || true
remote_wp "wp eval 'if ( function_exists( \"wp_cache_clear_cache\" ) ) { wp_cache_clear_cache(); echo \"wp-super-cache cleared\\n\"; }'" || true

if [[ "${SKIP_VERIFY}" -eq 0 ]]; then
  echo "=== Verify ${VERIFY_URL} ==="
  html=$(curl -sL --max-time 30 "${VERIFY_URL}" || true)
  if echo "${html}" | grep -q "${VERIFY_GREP}"; then
    echo "OK: homepage contains \"${VERIFY_GREP}\""
  else
    echo "WARN: homepage did not match \"${VERIFY_GREP}\" — hard-refresh or check Reading settings" >&2
    exit 1
  fi
fi

echo "=== Done ==="
remote_wp "wp option get stylesheet"
