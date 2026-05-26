---
name: deploy-dreamhost
description: >-
  Deploy and sync Blue Starfish Guesthouse on DreamHost via bash scripts only.
  Use for rsync push/pull, routine updates, full deploy with Ocean Breeze
  activation, cache flush, or when the user mentions DreamHost, deploy, sync,
  update production, or push live.
---

# Deploy DreamHost (Blue Starfish)

All remote deploy operations use scripts in **`scripts/`** relative to this skill. Do not run ad-hoc `ssh`, `scp`, or inline `rsync` unless a script is missing and the user explicitly asks.

**Production checks** belong in skill **verify-production** — see [verify-production/SKILL.md](../verify-production/SKILL.md). `update.sh` and `deploy.sh` call `verify-all.sh` unless `--skip-verify`.

Scripts root (from repo root):

```text
.agents/skills/deploy-dreamhost/scripts/
```

Requires SSH host **`bluestarfish`** and **full network** (rsync + SSH).

## Choose a script

| Task | Script |
|------|--------|
| Routine theme/file push + cache flush + verify | `scripts/update.sh` |
| First deploy, activate Ocean Breeze, site title | `scripts/deploy.sh` |
| Push files only (no WP-CLI, no verify) | `scripts/sync-up.sh` |
| Mirror server → repo (`--delete` locally) | `scripts/sync-down.sh` or `scripts/update.sh --pull` |
| Smoke tests only | `../verify-production/scripts/verify-all.sh` |

From repo root:

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh
.agents/skills/deploy-dreamhost/scripts/deploy.sh
.agents/skills/verify-production/scripts/verify-all.sh
```

## update.sh (default after theme is live)

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh
```

Runs: `sync-up.sh` → flush caches → **verify-production** (`verify-all.sh`).

| Flag | Effect |
|------|--------|
| `--pull` | `sync-down.sh` only (mirror; deletes extra local files) |
| `--files-only` | Rsync only; skip cache flush and verify |
| `--cache-only` | Flush caches; skip rsync |
| `--skip-verify` | Skip verify-production |

Does **not**: unzip theme zip, activate theme, or change `blogname` / `blogdescription`.

## deploy.sh (first-time / re-activate)

```bash
.agents/skills/deploy-dreamhost/scripts/deploy.sh
```

Runs: ensure theme (unzip `ocean-breeze.zip` if needed) → `sync-up.sh` → `wp theme activate ocean-breeze` → site title/tagline → cache flush → **verify-production**.

| Flag | Effect |
|------|--------|
| `--install-zip` | Force unzip `ocean-breeze.zip` |
| `--skip-sync` | Skip rsync |
| `--skip-activate` | Skip theme activation |
| `--skip-verify` | Skip verify-production |

## sync-up.sh / sync-down.sh

- **sync-up:** repo → DreamHost; remote-only files kept.
- **sync-down:** DreamHost → repo with `--delete`. Do not pull before theme exists on both sides.

## Never synced (config.sh)

`.agents/`, `.cursor/`, `.git/`, `scrape/`, `AGENTS.md`, `ocean-breeze.zip`, `wp-config.php`, `local-keys.php`, `local-smtp.php`, `.smtp-credentials`

## Safety checklist (before sync-up)

- [ ] Edits are under repo root (theme, `robots.txt`, etc.), not only under `.agents/`
- [ ] `wp-config.php` won't be overwritten unintentionally after a pull
- [ ] Run `verify-all.sh` after push if you used `--skip-verify` or `sync-up.sh` alone

## Environment overrides

```bash
REMOTE_HOST=bluestarfish REMOTE_DIR=bluestarfishguesthouse.com \
  .agents/skills/deploy-dreamhost/scripts/update.sh

SITE_NAME="Blue Starfish Guest Houses in the Corpus Christi bay area." \
SITE_TAGLINE="Corpus Christi bay-area stays" \
  .agents/skills/deploy-dreamhost/scripts/deploy.sh

VERIFY_GREP="Guesthouse" .agents/skills/verify-production/scripts/verify-all.sh
```

## Troubleshooting

| Problem | Action |
|---------|--------|
| rsync/ssh exit 255 | Run outside sandbox; test `ssh bluestarfish` |
| Theme missing locally | `deploy.sh --install-zip` |
| Old theme on site | `deploy.sh`; hard-refresh; clear WP Super Cache in admin |
| Changes not visible | Full `update.sh` (not `--files-only`) |
| Wrong homepage | Check **Settings → Reading** on server via WP-CLI in deploy flow |
| Need a new live-site check | Add to verify-production `verify.sh`, not ad-hoc curl/ssh |

## Related skills

| Skill | Role |
|-------|------|
| `verify-production` | Post-deploy smoke tests; extend `verify.sh` for new checks |
| `manage-theme` | Edit Ocean Breeze templates, `theme.json`, patterns |
| `manage-content` | Copy, robots.txt, launch checklist |

See [AGENTS.md](../../../AGENTS.md) for SSH, server paths, and agent rules.
