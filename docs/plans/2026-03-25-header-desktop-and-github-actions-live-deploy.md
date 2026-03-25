# Header Desktop And GitHub Actions Live Deploy Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Refresh the Perfex admin desktop header and move production deployment to a GitHub Actions driven `release/live` flow.

**Architecture:** Keep the admin header changes desktop-only and layer them onto the existing Perfex markup so mobile behavior remains intact. Ship production releases as packaged artifacts that include Composer vendors, upload them over SSH, and rsync into the live app root while preserving environment-owned files and user-generated content.

**Tech Stack:** Perfex CRM, CodeIgniter, PHP, CSS, GitHub Actions, Composer, npm/Grunt, rsync, SSH

---

### Task 1: Capture Production Fixes In Git

**Files:**
- Modify: `application/controllers/admin/Knowledge_base.php`
- Modify: `modules/custom_pdf/assets/js/custom_pdf.js`
- Modify: `modules/custom_pdf/includes/assets.php`
- Modify: `modules/project_roadmap/project_roadmap.php`
- Modify: `modules/project_roadmap/views/project_roadmap_dashboard_js.php`
- Modify: `modules/project_roadmap/views/project_roadmap_js.php`

**Step 1: Diff repo files against the known-good live mirror**

Run: `diff -u <repo-file> <live-mirror-file>`
Expected: only the already-verified hotfixes appear.

**Step 2: Apply the hotfixes exactly**

- guard `Knowledge_base` group edit flow so `id=0` is handled as edit
- guard `custom_pdf` colorpicker hooks against missing `data-*`
- switch `custom_pdf` script versioning to `filemtime()`
- stop `project_roadmap` dashboard JS from loading on every admin page
- guard `circleProgress()` calls in roadmap views

**Step 3: Verify PHP syntax**

Run:
- `php -l application/controllers/admin/Knowledge_base.php`
- `php -l modules/custom_pdf/includes/assets.php`
- `php -l modules/project_roadmap/project_roadmap.php`

Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add application/controllers/admin/Knowledge_base.php \
  modules/custom_pdf/assets/js/custom_pdf.js \
  modules/custom_pdf/includes/assets.php \
  modules/project_roadmap/project_roadmap.php \
  modules/project_roadmap/views/project_roadmap_dashboard_js.php \
  modules/project_roadmap/views/project_roadmap_js.php
git commit -m "fix: capture live hotfixes in repository"
```

### Task 2: Refresh The Desktop Header

**Files:**
- Modify: `application/views/admin/includes/header.php`
- Modify: `assets/css/style.css`
- Modify: `assets/css/style.min.css`

**Step 1: Write down the UI target before editing**

- visual thesis: a command-bar header with calmer hierarchy and stronger search focus
- content plan: brand rail, search rail, utility rail
- interaction thesis: sharper focus state for search, chip-style utility buttons, cleaner hover feedback

**Step 2: Update the desktop header markup**

- keep existing data hooks and dropdown behavior
- regroup controls into left, center, and right clusters
- keep mobile header/menu logic intact

**Step 3: Add desktop-only CSS**

- increase desktop header height and visual structure
- strengthen logo/search layout
- convert settings and utility actions into a consistent utility rail
- preserve current mobile breakpoints

**Step 4: Rebuild minified CSS**

Run: `npx grunt cssmin`
Expected: `assets/css/style.min.css` is regenerated

**Step 5: Verify**

Run:
- `php -l application/views/admin/includes/header.php`
- `test -f assets/css/style.min.css`

Expected: syntax passes and minified stylesheet exists

**Step 6: Commit**

```bash
git add application/views/admin/includes/header.php assets/css/style.css assets/css/style.min.css
git commit -m "feat: refresh admin desktop header"
```

### Task 3: Add Release Packaging And Deploy Scripts

**Files:**
- Create: `scripts/package-release.sh`
- Create: `scripts/install-release.sh`
- Create: `scripts/release-smoke.sh`
- Modify: `.gitignore`

**Step 1: Create a release packaging script**

- stage the repository into a temp directory
- exclude git metadata, local caches, docs not needed at runtime, and CI-only files
- keep application code, modules, built assets, and Composer vendors
- emit a tarball into `release-artifacts/`

**Step 2: Create a remote install script**

- extract the archive into a temp release directory on the server
- rsync into the live app root
- preserve `application/config/app-config.php`, `uploads/`, `media/`, `application/logs/`, and other environment-owned directories
- write a small `release.json` for traceability

**Step 3: Create smoke checks**

- hit `/authentication/login`
- hit `/admin/`
- assert the responses are reachable and not `500`

**Step 4: Ignore generated artifacts**

Add:
- `.worktrees/`
- `release-artifacts/`

**Step 5: Verify**

Run:
- `bash -n scripts/package-release.sh`
- `bash -n scripts/install-release.sh`
- `bash -n scripts/release-smoke.sh`

Expected: shell syntax passes

**Step 6: Commit**

```bash
git add .gitignore scripts/package-release.sh scripts/install-release.sh scripts/release-smoke.sh
git commit -m "chore: add release packaging and deploy scripts"
```

### Task 4: Add GitHub Actions And Branching Docs

**Files:**
- Create: `.github/workflows/build-release-artifact.yml`
- Create: `.github/workflows/deploy-live.yml`
- Create: `docs/branching-and-deploy.md`
- Modify: `README.md`

**Step 1: Add artifact build workflow**

- trigger on push to `release/live`
- set up PHP and Composer
- install root and module Composer dependencies
- install npm dependencies needed for CSS minification
- package release artifact
- upload artifact

**Step 2: Add live deploy workflow**

- trigger automatically on push to `release/live`
- build the release package
- prepare SSH key from GitHub secrets
- upload archive to the server
- execute the remote install script
- run smoke checks against production

**Step 3: Document branch strategy**

- `main`: integration branch
- `release/live`: production branch, auto deploys on push
- `feat/*`, `fix/*`, `chore/*`, `codex/*`: working branches

**Step 4: Verify workflow syntax**

Run:
- `python - <<'PY' ... yaml.safe_load(...)`
- or `ruby -e "require 'yaml'; ..."` if Python YAML is unavailable

Expected: both workflows parse cleanly

**Step 5: Commit**

```bash
git add .github/workflows/build-release-artifact.yml .github/workflows/deploy-live.yml docs/branching-and-deploy.md README.md
git commit -m "ci: add release live auto deploy workflow"
```

### Task 5: Provision Secrets And Publish Branches

**Files:**
- Modify: remote GitHub repository settings/secrets
- Modify: live server SSH authorized keys

**Step 1: Generate a deploy key**

Run: `ssh-keygen -t ed25519 -f <path> -N "" -C "github-actions@portal.chantroituonglai.com"`
Expected: private/public key pair created

**Step 2: Install the public key on the server**

- append the `.pub` key to the deploy user `authorized_keys`
- verify SSH key login works without a password

**Step 3: Set GitHub Actions secrets**

- `PROD_SSH_HOST`
- `PROD_SSH_USER`
- `PROD_SSH_KEY`
- `PROD_APP_ROOT`
- `PROD_BASE_URL`

**Step 4: Publish branch structure**

Run:
- `git push origin main`
- `git push origin HEAD:refs/heads/release/live`

Expected: `release/live` exists remotely and the deploy workflow runs automatically.

**Step 5: Verify production deployment**

- inspect GitHub Actions run result
- run smoke checks against `https://portal.chantroituonglai.com`
- confirm header assets and hotfixes are live

**Step 6: Commit admin note**

Document the live deploy expectations in `docs/branching-and-deploy.md` if any server-specific adjustments were required.
