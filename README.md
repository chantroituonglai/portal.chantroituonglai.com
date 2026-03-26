# Portal Chantroituonglai

Production repository for `portal.chantroituonglai.com`, built on **Perfex CRM 3.4.1**.

This repo is the tracked application root (`public_html`) of the portal. It contains the live Perfex app, custom modules, deployment workflows, and the scripts used to package and release code to production.

## Current State

- App type: `Perfex CRM` / `CodeIgniter 3`
- Current app version: `3.4.1`
- Production URL: [https://portal.chantroituonglai.com/](https://portal.chantroituonglai.com/)
- Local beta URL: `http://beta.portal.chantroituonglai.com/`
- Git default branch: `main`
- Production release branch: `release/live`

Version reference:
- [`application/config/migration.php`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/application/config/migration.php)

## Repository Layout

This repository maps to the actual application root, not the parent XAMPP folder.

- `application/`: core Perfex application code and configuration loaders
- `assets/`: compiled CSS/JS and theme assets
- `modules/`: custom and third-party Perfex modules
- `scripts/`: release packaging, install, and smoke-check helpers
- `.github/workflows/`: build and deploy automation
- `docs/`: operational notes and branch/deploy documentation
- `uploads/`, `media/`, `application/logs/`: runtime data, not part of normal production overwrite

## Notable Modules In Use

The portal currently includes a large module set. Some notable integrations and custom surfaces in active use:

- `openclaw_gateway`
- `chatpion_bridge`
- `einvoice`
- `delivery_notes`
- `flutex_admin_api`
- `vietnam_addresses`
- `warehouse`
- `woocommerce`
- `mailbox`
- `theme_style`
- `custom_pdf`
- `hrm`
- `okr`
- `affiliate_management`
- `purchase`
- `products`

For a broader view, inspect [`modules/`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/modules).

## Local Development

This project is usually operated from the parent workspace:

- workspace root: `/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com`
- app root tracked by Git: `/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html`

Typical local setup on the current machine:

1. Mirror code from live into the parent workspace.
2. Serve the app as `beta.portal.chantroituonglai.com` through local Apache/XAMPP.
3. Point local beta to the live database or an SSH tunnel to the live database.
4. Keep `application/config/app-config.php` environment-specific and out of release overwrite.

Related operational files:
- [`docs/branching-and-deploy.md`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/docs/branching-and-deploy.md)
- [`scripts/package-release.sh`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/scripts/package-release.sh)
- [`scripts/install-release.sh`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/scripts/install-release.sh)
- [`scripts/release-smoke.sh`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/scripts/release-smoke.sh)

## Branching Model

- `main`: integration branch
- `release/live`: production branch, auto-deployed by GitHub Actions on push
- `feat/*`: feature branches
- `fix/*`: bugfix branches
- `chore/*`: maintenance, docs, CI, housekeeping
- `codex/*`: short-lived agent branches

Recommended flow:

1. Branch from `main`
2. Implement and verify locally
3. Merge back into `main`
4. Promote selected changes from `main` into `release/live`
5. Push `release/live` to trigger production deployment

## GitHub Actions Deploy

The repo currently ships with two workflows:

- [`build-release-artifact.yml`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/.github/workflows/build-release-artifact.yml)
- [`deploy-live.yml`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/.github/workflows/deploy-live.yml)

Deploy characteristics:

- production is deployed from `release/live`, not directly from `main`
- release artifact excludes environment-owned and runtime-owned paths
- deploy uses `rsync` onto the live app root
- smoke check runs against login/admin endpoints after deploy

Paths intentionally preserved during deploy:

- `application/config/app-config.php`
- `uploads/`
- `media/`
- `application/logs/`
- backups and host-owned runtime files

Required GitHub secrets:

- `PROD_SSH_HOST`
- `PROD_SSH_USER`
- `PROD_SSH_KEY`
- `PROD_APP_ROOT`
- `PROD_BASE_URL`

## Asset Notes

- This repo contains both [`assets/css/style.css`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/assets/css/style.css) and [`assets/css/style.min.css`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/assets/css/style.min.css).
- In day-to-day hotfixes, keep these two files synchronized if you are patching header/UI styles directly.
- `Gruntfile.js`, `package.json`, and `package-lock.json` are present, but most production hotfixes in this repo have been applied directly to compiled assets.

## Important Operational Cautions

- Do not commit real secrets.
- Do not overwrite `application/config/app-config.php` during release packaging.
- Be careful when local beta is pointed at the live database.
- Do not use FTP mirror as the production deployment mechanism; production deploys through GitHub Actions on `release/live`.

## Quick Verification

Useful checks after touching production-facing code:

```bash
php -l application/config/migration.php
php -l application/views/admin/includes/header.php
```

```bash
./scripts/release-smoke.sh
```

```bash
git branch --show-current
git status --short
```

## References

- [`docs/branching-and-deploy.md`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/docs/branching-and-deploy.md)
- [`application/config/migration.php`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/application/config/migration.php)
- [`modules/openclaw_gateway`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/modules/openclaw_gateway)
- [`modules/chatpion_bridge`](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/modules/chatpion_bridge)
