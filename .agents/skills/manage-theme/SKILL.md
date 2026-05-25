---
name: manage-theme
description: >-
  Edit the Ocean Breeze WordPress block theme (FSE) for Blue Starfish Guesthouse.
  Use when changing templates, patterns, theme.json, styles, fonts, or Site
  Editor layout for ocean-breeze.
---

# Manage theme (Ocean Breeze)

## Location

All theme source: `wp-content/themes/ocean-breeze/`

| Path | Role |
|------|------|
| `theme.json` | Palette, typography, spacing |
| `templates/*.html` | Page templates |
| `parts/header.html`, `parts/footer.html` | Template parts |
| `patterns/*.php` | Block patterns |
| `assets/` | CSS extras, bundled woff2 fonts |
| `style.css` | Theme metadata |

## Deploy after edits

Use **deploy-dreamhost** scripts only:

```bash
.agents/skills/deploy-dreamhost/scripts/update.sh
```

Files only (no cache flush):

```bash
.agents/skills/deploy-dreamhost/scripts/sync-up.sh
```

First-time install or re-activate theme:

```bash
.agents/skills/deploy-dreamhost/scripts/deploy.sh
```

Then verify in wp-admin **Appearance → Editor**. Hard-refresh if CSS is stale.

## Conventions

- Match palette names in `theme.json` (Deep Twilight, Turquoise Surf, Paper, etc.).
- Fonts are **bundled** in `assets/fonts/` — no Google Fonts CDN.
- Valid block markup; test front-page and single after structural edits.

## Not Elementor

Do not use Elementor MCP or widget APIs. Layout is blocks + Site Editor.

## New pattern or template

1. Copy an existing template/pattern.
2. Register patterns in `patterns/` with block-theme headers.
3. Run `update.sh`; assign template in Site Editor if needed.

See theme `README.md` for palette table.
