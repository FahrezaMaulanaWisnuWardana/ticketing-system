# Internal Ticketing System

A single-company helpdesk, dev-work tracker and project manager. opinionated, few fields, one workflow.

**Current state: Phase 7 (Production hardening) complete — all mandatory phases delivered.** Later phases (SLA, board, reports, integrations) are not built yet.

## Stack

PHP 8.3, Laravel 13.43, PostgreSQL 16, Redis 7, Livewire 3, Alpine.js, Tailwind CSS 3, Vite, Socialite, Horizon, Reverb, Sanctum, Pest 4, Larastan 8, Pint.

> Laravel 13 replaces the originally specified Laravel 11: Laravel 11 has unpatched high/critical advisories that fail the mandatory `composer audit` gate.

## Setup in 5 commands

```bash
cp .env.example .env
docker compose up -d --build --wait
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed   # local/staging only: demo data
```

Open <http://localhost:8000>. Demo accounts (local only): `admin@example.com`, `lead@example.com`, `agent1@example.com`, `user1@example.com` — password `password123`.

The dev stack starts Postgres 16, Redis 7, PHP-FPM, nginx, Horizon, the scheduler, Reverb and Mailpit (web UI at <http://localhost:8025>). Optional profiles: `docker compose --profile vite up -d` (Vite dev server on 5173) and `--profile minio` (object storage).

Google login requires `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` and `ALLOWED_EMAIL_DOMAINS` in `.env`.

## Everyday commands

```bash
./vendor/bin/pest                      # tests
./vendor/bin/pint --test               # style
./vendor/bin/phpstan analyse --memory-limit=1G   # level 8
docker compose logs -f app nginx horizon reverb scheduler
php artisan migrate:rollback --force   # verify reversibility
```

## Documentation

- [UI craft rules](docs/DESIGN.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Security](docs/SECURITY.md)
- [Deploy](docs/DEPLOY.md)
- [Runbook](docs/RUNBOOK.md)
- [Admin guide](docs/ADMIN-GUIDE.md)
- [User guide](docs/USER-GUIDE.md)
- [Load test](docs/LOADTEST.md)
- [Changelog](docs/CHANGELOG.md)
