---
name: manage-content
description: >-
  Content, SEO, and pre-launch tasks for bluestarfishguesthouse.com. Use when
  writing copy, editing robots.txt, noindex/staging, Rank Math or meta, or
  preparing the guesthouse site for launch.
---

# Manage content (Blue Starfish)

## Site context

Vacation rental / guesthouse marketing site. Theme: **Ocean Breeze** (editorial, navy + turquoise). Reference copy in `scrape/` (local only, not synced).

## Deploy after file edits

Use **deploy-dreamhost** scripts only:

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh
```

For `robots.txt` with no cache concern:

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh --files-only
```

To capture server-side edits:

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh --pull
```

## robots.txt (pre-launch)

Path at repo root; synced on push.

While building:

- Block crawlers or disallow all if the site should stay private.
- Note in commit/task when rules are relaxed for launch.

After launch:

- Remove blanket `Disallow: /`.
- Keep sensible defaults (allow site; restrict `/wp-admin/`).

## Copy workflow

1. **Static copy in theme files** — edit templates/patterns under `wp-content/themes/ocean-breeze/` (skill `manage-theme`), then `update.sh`.
2. **Database content** — wp-admin **Pages** / **Site Editor**, or WP-CLI via deploy scripts' SSH (no ad-hoc ssh unless scripts unavailable).

Sources: `scrape/1/info.md`, `scrape/2/info.md`.

## SEO checklist

- One clear H1 per page
- Title and meta description (Rank Math or similar if installed)
- Local/guesthouse keywords where natural
- Image alt text on hero and gallery images

## Launch checklist

- [ ] robots.txt allows indexing
- [ ] Remove staging noindex if added via plugin
- [ ] Theme active: Ocean Breeze (`deploy.sh` if needed)
- [ ] Forms and contact tested
- [ ] `update.sh` for final files; verify production URL
