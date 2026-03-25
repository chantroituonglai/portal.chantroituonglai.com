#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:?BASE_URL is required}"

check_url() {
  local url="$1"
  local expected_fragment="$2"
  local headers

  headers="$(curl -k -I -sS "$url")"
  printf '%s\n' "$headers"

  if ! grep -Eq '^HTTP/[0-9.]+ (200|301|302|303)' <<<"$headers"; then
    echo "Unexpected HTTP status for $url" >&2
    exit 1
  fi

  if [[ -n "$expected_fragment" ]] && ! grep -Fiq "$expected_fragment" <<<"$headers"; then
    echo "Expected fragment '$expected_fragment' not found for $url" >&2
    exit 1
  fi
}

check_url "$BASE_URL/authentication/login" "HTTP/"
check_url "$BASE_URL/admin/" ""
