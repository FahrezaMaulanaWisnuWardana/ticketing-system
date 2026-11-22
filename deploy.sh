#!/usr/bin/env bash
set -euo pipefail

# Usage: ./deploy.sh <staging|production> <git-sha>
ENVIRONMENT="${1:?environment required}"
SHA="${2:?image tag required}"
IMAGE="registry.example.com/ticketing:${SHA}"

echo "[deploy] building ${IMAGE}"
docker build -f docker/prod/Dockerfile -t "${IMAGE}" .
docker push "${IMAGE}"

echo "[deploy] pre-deploy database snapshot"
pg_dump -Fc "${DB_URL}" > "backups/pre-deploy-${SHA}.dump"

echo "[deploy] running migrations"
IMAGE="${IMAGE}" docker compose -f docker-compose.prod.yml run --rm app php artisan migrate --force

echo "[deploy] starting services"
IMAGE="${IMAGE}" docker compose -f docker-compose.prod.yml up -d --wait --remove-orphans

echo "[deploy] rebuilding caches"
IMAGE="${IMAGE}" docker compose -f docker-compose.prod.yml exec -T app sh -c \
  'php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache'

echo "[deploy] smoke test"
./scripts/smoke.sh

echo "[deploy] graceful restart of workers"
IMAGE="${IMAGE}" docker compose -f docker-compose.prod.yml exec -T app php artisan horizon:terminate

echo "[deploy] done (${ENVIRONMENT}, ${SHA})"
