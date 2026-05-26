# Shared settings for deploy-dreamhost scripts (sourced, not executed).

REMOTE_HOST="${REMOTE_HOST:-bluestarfish}"
REMOTE_DIR="${REMOTE_DIR:-bluestarfishguesthouse.com}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../../../.." && pwd)"

# Paths excluded from both directions (never synced to/from the server).
RSYNC_EXCLUDES=(
  --exclude '.agents/'
  --exclude '.cursor/'
  --exclude '.git/'
  --exclude '.worktrees/'
  --exclude 'scrape/'
  --exclude 'AGENTS.md'
  --exclude 'ocean-breeze.zip'
  --exclude 'local-keys.php'
  --exclude 'local-smtp.php'
  --exclude '.smtp-credentials'
  --exclude 'wp-config.php'
)
