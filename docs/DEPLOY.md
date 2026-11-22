# Deployment

## Images

| Image | Command | Notes |
|---|---|---|
| `app` | `php-fpm` | opcache with `validate_timestamps=0`, non-root, 256M |
| `web` | nginx | static caching for hashed Vite assets, dotfiles denied, proxy for Reverb `wss://` |
| `worker` | `php artisan horizon` | separate queues |
| `reverb` | `php artisan reverb:start` | scaling via Redis |
| `scheduler` | `php artisan schedule:work` | single instance with `onOneServer()` later |

All images come from `docker/prod/Dockerfile` / `Dockerfile.web`. Secrets: Docker secrets or env file outside the repository.

## Steps

1. Build image tagged with git SHA; scan with Trivy; push.
2. Snapshot the database (`pg_dump -Fc`).
3. If the migration is backward compatible, no downtime; otherwise `php artisan down --secret=...`.
4. `php artisan migrate --force` as a one-off before traffic switch.
5. `docker compose -f docker-compose.prod.yml up -d --remove-orphans`; `horizon:terminate`; `reverb:restart`.
6. Rebuild caches (`config:cache`, `route:cache`, `view:cache`, `event:cache`).
7. Run `./scripts/smoke.sh`.

`./deploy.sh <staging|production> <git-sha>` performs these steps.

## Rollback

Redeploy the previous image tag. Migrations must remain backward compatible for one release (expand/contract). If not, restore the pre-deploy snapshot with `scripts/restore-test.sh` adapted to the production target (requires maintenance window).

## Environments

local → staging (production-like, anonymised demo data) → production. Same images, different env files.
