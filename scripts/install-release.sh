#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="${APP_ROOT:?APP_ROOT is required}"
RELEASE_ID="${RELEASE_ID:?RELEASE_ID is required}"
ARCHIVE_PATH="${ARCHIVE_PATH:?ARCHIVE_PATH is required}"
APP_SLUG="${APP_SLUG:-portal-chantroituonglai}"
REMOTE_RELEASES_DIR="${REMOTE_RELEASES_DIR:-/tmp/${APP_SLUG}-releases}"
RELEASE_DIR="$REMOTE_RELEASES_DIR/$RELEASE_ID"

mkdir -p "$REMOTE_RELEASES_DIR"
rm -rf "$RELEASE_DIR"
mkdir -p "$RELEASE_DIR"

tar -xzf "$ARCHIVE_PATH" -C "$RELEASE_DIR" --strip-components=1

if [[ ! -f "$RELEASE_DIR/index.php" ]]; then
  echo "Release archive is missing index.php after extraction: $RELEASE_DIR" >&2
  exit 1
fi

rsync -a \
  --delete \
  --exclude 'application/config/app-config.php' \
  --exclude 'uploads' \
  --exclude 'media' \
  --exclude 'application/logs' \
  --exclude 'backup' \
  --exclude 'backups' \
  --exclude '.user.ini' \
  "$RELEASE_DIR/" "$APP_ROOT/"

echo "$RELEASE_DIR"
