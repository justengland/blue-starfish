# Blue Starfish Guesthouse — agent notes

WordPress site for **https://bluestarfishguesthouse.com/** on DreamHost shared hosting. The live document root is `~/bluestarfishguesthouse.com/` on the server.

## How this repo is organized

| Area | Purpose |
|------|---------|
| `wp-content/themes/ocean-breeze/` | Custom FSE block theme (source of truth for layout) |
| `.agents/skills/` | Agent skills and scripts — **never synced** to DreamHost |
| `scrape/` | Local research captures — **never synced** |

Read **AGENTS.md** first for stack and hosting context. Use **project skills** under `.agents/skills/` for repeatable workflows.

## Agent rules: deploy and verify

**Deploy** — Use skill **deploy-dreamhost** only. Run scripts under `.agents/skills/deploy-dreamhost/scripts/`. Do not run ad-hoc `rsync`, `scp`, or inline `ssh` for routine push/pull unless a script is missing and the user explicitly asks.

**Verify** — Use skill **verify-production** only. Run `.agents/skills/verify-production/scripts/verify-all.sh` (or let `update.sh` / `deploy.sh` invoke it). Do **not** run one-off production checks (`curl` against the live site, `ssh … grep` on theme files, `wp eval` smoke tests) for the same purpose.

**Adding a new production check** — Implement `verify_<name>()` in [verify-production/scripts/verify.sh](.agents/skills/verify-production/scripts/verify.sh), call it from `verify_deploy()`, and document it in [verify-production/SKILL.md](.agents/skills/verify-production/SKILL.md). Never leave the check only in chat or a one-off command.

## Remote access (DreamHost)

SSH/SFTP on the developer machine uses host alias **`bluestarfish`** in `~/.ssh/config`:

```
Host bluestarfish
  HostName iad1-shared-b8-01.dreamhost.com
  User dh_tfg2cb
  Port 22
```

Do **not** store passwords, API tokens, or private keys in this repo.

OpenSSH may warn about post-quantum key exchange on DreamHost shared servers; safe to ignore for file deploys.

### Server paths

| Path | Purpose |
|------|---------|
| `~/` | SFTP user home |
| `~/bluestarfishguesthouse.com/` | WordPress web root (sync target) |
| `~/bluestarfishguesthouse.com/robots.txt` | Crawl rules (edit during pre-launch staging) |

## Deploy workflow (deploy-dreamhost)

Read [.agents/skills/deploy-dreamhost/SKILL.md](.agents/skills/deploy-dreamhost/SKILL.md) before deploying.

| Script | When |
|--------|------|
| `update.sh` | Routine push + cache flush + verify-production (theme already active) |
| `deploy.sh` | First deploy, install/activate Ocean Breeze, site title |
| `sync-up.sh` | Files only, repo → server |
| `sync-down.sh` | Mirror server → repo (`--delete` locally) |

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh
.agents/skills/deploy-dreamhost/scripts/deploy.sh
```

| Flag | Effect |
|------|--------|
| `--skip-verify` | Skip verify-production (deploy only) |
| `--files-only` | Rsync only; no cache flush or verify |
| `--pull` | `sync-down.sh` only |

**Never synced** (see [deploy-dreamhost/scripts/config.sh](.agents/skills/deploy-dreamhost/scripts/config.sh)): `.agents/`, `.cursor/`, `.git/`, `scrape/`, `AGENTS.md`, `ocean-breeze.zip`, `wp-config.php`, `local-keys.php`, `local-smtp.php`, `.smtp-credentials`.

Override: `REMOTE_HOST=bluestarfish REMOTE_DIR=bluestarfishguesthouse.com .agents/skills/deploy-dreamhost/scripts/sync-up.sh`

## Verify workflow (verify-production)

Read [.agents/skills/verify-production/SKILL.md](.agents/skills/verify-production/SKILL.md) before adding or running checks.

```bash
.agents/skills/verify-production/scripts/verify-all.sh
```

Runs after every `update.sh` / `deploy.sh` unless `--skip-verify`. Current checks: homepage grep, contact form markup on the contact URL, contact form PHP on the server.

Override examples: `VERIFY_GREP="Guesthouse"` or `VERIFY_CONTACT_URL=…` (see verify skill).

## WordPress stack

- **Theme:** Ocean Breeze — block theme (FSE), not Elementor. Customize via **Appearance → Editor** or `wp-content/themes/ocean-breeze/`.
- **Plugins:** Standard WP install on DreamHost; avoid bulk-editing plugin/vendor trees unless fixing a specific issue.
- **Config:** `wp-config.php` exists on server and locally after sync-down; never commit secrets.

### Site Editor vs theme files (FSE)

Block themes ship templates and template parts as files under `wp-content/themes/ocean-breeze/`. Once someone edits in **Appearance → Editor**, WordPress stores a **customized copy in the database** (`wp_template`, `wp_template_part`). That DB version is what visitors see — rsync/deploy updates the theme files only and does **not** replace those saved customizations.

**Prefer working with WordPress, not forcing it:**

- After `update.sh`, if layout changes are not visible, assume a DB override or cache — do **not** use WP-CLI to overwrite template parts from disk unless the user explicitly asks.
- Normal path: user applies or reverts changes in the Site Editor, or pulls server state into the repo with `sync-down.sh` when their editor work should become the source of truth.
- If the user edited only in the Site Editor, their work lives on the server until synced down; repo edits and deploy alone will not update the live header/footer until they align editor + theme or choose to reset a template part in the editor.

**Deploy still matters:** theme files on disk are the repo’s source of truth for version control and the baseline for new installs; the editor is the source of truth for customized production layout until you intentionally reconcile the two.

## Staging / pre-launch

While the site is under construction, keep **robots.txt** restrictive (or noindex via plugin). Remove or relax blocking rules before launch.

## Optional tooling (not required)

The [Claude Code for WordPress workflow doc](https://docs.google.com/document/d/1-V32ZLxfA8ZJ1G-OnHWz0TPYwXcw6TwOfM1Jnr0mZZ0/edit) describes Playwright and Lighthouse MCP servers. Elementor MCP does **not** apply — this site uses the block theme.

## Skills index

| Skill | When to use |
|-------|-------------|
| `deploy-dreamhost` | Push/pull, routine update, full deploy, cache flush |
| `verify-production` | Post-deploy smoke tests; extend `verify.sh` for new checks |
| `manage-theme` | Templates, patterns, `theme.json`, Site Editor |
| `manage-content` | Copy, SEO, robots.txt, launch checklist |
