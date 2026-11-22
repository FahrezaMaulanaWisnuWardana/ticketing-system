#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost}"

login="$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}/login")"
up="$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}/up")"

echo "login=${login} up=${up}"
[ "${login}" = "200" ]
[ "${up}" = "200" ]

echo "Smoke test passed. Create+delete service-account ticket check is performed by the Phase 7 smoke suite."
