#!/usr/bin/env bash
set -euo pipefail
export LC_ALL=C
export COPYFILE_DISABLE=1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
APP_SLUG="${APP_SLUG:-portal-chantroituonglai}"
RELEASE_ID="${RELEASE_ID:-$(date -u +%Y%m%d-%H%M%S)-artifact}"
OUTPUT_DIR="${OUTPUT_DIR:-$PROJECT_ROOT/release-artifacts}"
STAGE_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${APP_SLUG}-stage-${RELEASE_ID}-XXXXXX")"
PACKAGE_ROOT="$STAGE_DIR/$APP_SLUG"
ARCHIVE_PATH="$OUTPUT_DIR/${APP_SLUG}-${RELEASE_ID}.tar.gz"

cleanup() {
  rm -rf "$STAGE_DIR"
}
trap cleanup EXIT

mkdir -p "$OUTPUT_DIR" "$PACKAGE_ROOT"

rsync -a \
  --delete \
  --exclude '.git' \
  --exclude '.github' \
  --exclude '.gitignore' \
  --exclude '.idea' \
  --exclude '.vscode' \
  --exclude '.DS_Store' \
  --exclude '.worktrees' \
  --exclude 'node_modules' \
  --exclude 'release-artifacts' \
  --exclude 'docs' \
  --exclude '*.md' \
  --exclude 'package.json' \
  --exclude 'package-lock.json' \
  --exclude 'Gruntfile.js' \
  --exclude 'scripts' \
  --exclude 'backups' \
  --exclude 'backup' \
  --exclude 'uploads' \
  --exclude 'media' \
  --exclude 'application/logs' \
  --exclude 'application/config/app-config.php' \
  --exclude '.user.ini' \
  --exclude 'upload_fixed_files.sh' \
  "$PROJECT_ROOT/" "$PACKAGE_ROOT/"

cat > "$PACKAGE_ROOT/release.json" <<EOF
{
  "app": "$APP_SLUG",
  "release_id": "$RELEASE_ID",
  "git_sha": "${GITHUB_SHA:-$(git -C "$PROJECT_ROOT" rev-parse HEAD 2>/dev/null || printf 'unknown')}",
  "built_at_utc": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
}
EOF

python3 - "$STAGE_DIR" "$APP_SLUG" "$ARCHIVE_PATH" <<'PY'
import os
import tarfile
from pathlib import Path
import sys

stage_dir = Path(sys.argv[1])
app_slug = sys.argv[2]
archive_path = Path(sys.argv[3])
source_root = stage_dir / app_slug

def clean_filter(ti: tarfile.TarInfo) -> tarfile.TarInfo:
    ti.uid = 0
    ti.gid = 0
    ti.uname = "root"
    ti.gname = "root"
    ti.pax_headers = {}
    ti.mtime = int(ti.mtime)
    return ti

with tarfile.open(archive_path, "w:gz", format=tarfile.PAX_FORMAT) as tf:
    tf.add(source_root, arcname=app_slug, filter=clean_filter)
PY

echo "$ARCHIVE_PATH"
