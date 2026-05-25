# Ocean Breeze

A clean, editorial WordPress **block theme** (Full Site Editing) built around the Ocean Breeze palette. Navy-dominant, serif display type, and one sharp turquoise accent — designed to look like studio work, not a template.

## Design language

- **Type:** Fraunces (a characterful variable serif) for display, Archivo for body, IBM Plex Mono for small labels and numerals. Fonts are **bundled** — no Google Fonts request, no external dependency, GDPR-friendly.
- **Color:** Deep Twilight navy carries the heavy sections; Turquoise Surf is the single loud accent; Light Cyan and Paper handle the quiet space. No pastel gradients.
- **Layout:** Asymmetric, hairline-ruled, numbered service rows — an editorial rather than "card grid" feel.

## Palette

| Name | Hex | Role |
|---|---|---|
| Deep Twilight | `#03045E` | Dominant — hero, CTA, footer rules |
| Bright Teal Blue | `#0077B6` | Links, labels |
| Turquoise Surf | `#00B4D8` | Primary accent, buttons |
| Frosted Blue | `#90E0EF` | Secondary text on navy |
| Light Cyan | `#CAF0F8` | Section backgrounds |
| Paper | `#FBFCFD` | Page background |
| Ink | `#0A1119` | Body text |

## Install

1. WordPress Admin → **Appearance → Themes → Add New → Upload Theme**.
2. Choose `ocean-breeze.zip`, install, activate.
3. **Appearance → Editor** to customize via the Site Editor.

Requires WordPress 6.4+ and PHP 7.4+.

## Contents

- **Templates:** front-page, index, single, page, page-no-title, page-wide, archive, search, 404
- **Parts:** header (clean, logo + uppercase nav), footer (three-column, mono detail line)
- **Pattern:** "Editorial Hero" (Patterns → Featured → Ocean Breeze)
- **Fonts:** `assets/fonts/` — Fraunces (roman + italic) and Archivo, weight-variable woff2

## Site title and tagline

The browser tab and header logo text come from **Settings → General** (or WP-CLI), not from theme templates:

| Setting | Recommended value |
|---|---|
| **Site title** | `Blue Starfish Guest Houses in the Corpus Christi bay area.` |
| **Tagline** | `Corpus Christi mid-term guesthouse rentals.` |

On DreamHost after deploy:

```bash
SITE_NAME='Blue Starfish Guest Houses in the Corpus Christi bay area.' \
SITE_TAGLINE='Corpus Christi mid-term guesthouse rentals.' \
.agents/skills/deploy-dreamhost/scripts/deploy.sh --skip-sync --skip-activate
```

Or manually: **Settings → General** in wp-admin.

## Customizing

Everything visual lives in **`theme.json`**. Change a palette hex and the whole site follows. Swap a `fontFamily` to change type. The Site Editor exposes colors, type, and spacing through the UI — no code needed for most edits.

To swap a bundled font, drop a new `.woff2` into `assets/fonts/` and update the matching `fontFace.src` in `theme.json`.

## License

Theme code: GPL v2 or later. Fonts: Fraunces and Archivo are licensed under the SIL Open Font License 1.1.
