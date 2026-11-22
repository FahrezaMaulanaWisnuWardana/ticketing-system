#!/usr/bin/env bash
set -euo pipefail

# Usage: BACKUP_FILE=backups/latest.dump SCRATCH_URL=postgres://... ./scripts/restore-test.sh
BACKUP_FILE="${BACKUP_FILE:?backup file required}"
SCRATCH_URL="${SCRATCH_URL:?scratch database URL required}"

echo "[restore-test] preparing scratch schema"
psql "${SCRATCH_URL}" -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;' >/dev/null
psql "${SCRATCH_URL}" -c 'CREATE EXTENSION IF NOT EXISTS citext; CREATE EXTENSION IF NOT EXISTS pg_trgm;' >/dev/null

echo "[restore-test] restoring dump"
pg_restore --no-owner --no-privileges --dbname="${SCRATCH_URL}" "${BACKUP_FILE}"

echo "[restore-test] sanity queries"
psql "${SCRATCH_URL}" -tAc 'select count(*) from users' | grep -Eq '^[0-9]+$'
psql "${SCRATCH_URL}" -tAc 'select count(*) from tickets' | grep -Eq '^[0-9]+$'
echo "[restore-test] OK"
