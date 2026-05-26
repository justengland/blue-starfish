---
name: verify-production
description: >-
  Run post-deploy smoke tests for bluestarfishguesthouse.com (homepage, contact
  form, remote theme files). Use after deploy or when validating production.
  Add new checks to verify.sh — do not run ad-hoc curl/ssh/wp eval for the same
  purpose.
---

# Verify production (Blue Starfish)

All production smoke tests live in **`scripts/verify.sh`**. Run them via **`scripts/verify-all.sh`**.

Do **not** run one-off `curl`, `ssh … grep`, or `wp eval` checks for the same purpose — extend `verify.sh` and call `verify-all.sh` instead.

Scripts root (from repo root):

```text
.agents/skills/verify-production/scripts/
```

Requires **full network** (curl + SSH). Uses `REMOTE_HOST` / `REMOTE_DIR` from [deploy-dreamhost `config.sh`](../deploy-dreamhost/scripts/config.sh).

## Run checks

```bash
.agents/skills/verify-production/scripts/verify-all.sh
```

`update.sh` and `deploy.sh` call this automatically unless `--skip-verify` is passed.

| Check | Function | What it does |
|-------|----------|----------------|
| Homepage | `verify_homepage` | `VERIFY_URL` contains `VERIFY_GREP` |
| Contact form file | `verify_contact_form_remote` | Server theme file has expected autoreply `from_name` |
| Contact page | `verify_contact_page` | `VERIFY_CONTACT_URL` renders `#contact-form` |
| Location page | `verify_location_page` | `VERIFY_LOCATION_URL` contains `VERIFY_LOCATION_GREP` (default `225 Waverly`) |

## Add a new check

1. Add `verify_<feature>()` in `scripts/verify.sh`.
2. Call it from `verify_deploy()`.
3. Document the check in this table.
4. Use `VERIFY_*` environment variables for URLs/strings (with defaults in `verify.sh`).

## Environment overrides

```bash
VERIFY_GREP="Guesthouse" .agents/skills/verify-production/scripts/verify-all.sh

VERIFY_CONTACT_URL="https://www.bluestarfishguesthouse.com/contact/" \
  .agents/skills/verify-production/scripts/verify-all.sh
```

## Related skills

| Skill | Role |
|-------|------|
| `deploy-dreamhost` | Push files, flush caches; invokes verify unless `--skip-verify` |
| `manage-content` | Launch checklist may reference verify after content changes |

See [AGENTS.md](../../../AGENTS.md) for agent rules and SSH paths.
