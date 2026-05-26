#!/usr/bin/env bash
# Run all production smoke tests (homepage, contact form file on server, contact page).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=../../deploy-dreamhost/scripts/config.sh
source "${SCRIPT_DIR}/../../deploy-dreamhost/scripts/config.sh"
# shellcheck source=verify.sh
source "${SCRIPT_DIR}/verify.sh"

usage() {
  cat <<EOF
Usage: $(basename "$0") [options]

  Post-deploy smoke tests for bluestarfishguesthouse.com. Add new checks in verify.sh.

Options:
  -h, --help    show this help

Environment:
  REMOTE_HOST, REMOTE_DIR     (see deploy-dreamhost config.sh)
  VERIFY_URL, VERIFY_GREP     homepage smoke test
  VERIFY_CONTACT_URL          contact page URL
  VERIFY_CONTACT_FORM_*       remote theme file checks
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
  esac
done

echo "=== Blue Starfish production verify ==="
verify_deploy
echo "=== Verify OK ==="
