# Internal Ticketing System

A single-company helpdesk, dev-work tracker and project manager. opinionated, few fields, one workflow.

**Current state: all phases are complete — Phase 1 (foundation) through Phase 7 (production hardening), plus the optional Phase 6b/6c Trello integration that was built on explicit request.** This includes SLA (Phase 3), dev work and the board (Phase 4), project management (Phase 5), and reports/integrations (Phase 6). See the [Changelog](docs/CHANGELOG.md) for per-phase detail. What remains is operational verification on the production host, tracked in the [Go-live checklist](docs/GO-LIVE.md).

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

To connect Navicat to the Docker Postgres: host `127.0.0.1`, **port `5433`** (port `5432` is used by the host's own PostgreSQL), database/user/password from `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env` (here: `ticketing` / `reza` / `secret`).

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

## Portal UI (Jira-style)

The requester portal was reshaped into two distinct pages:

- **Beranda** (`/`) — no ticket table. It has a debounced live search (Alpine, 300 ms) over request types and help articles, grouped request-type cards, and a small "Permintaan terbaru saya" list (max 5).
- **Permintaan saya** (`/requests`) — server-rendered list with scoped filters, sortable columns, 20-per-page pagination, empty state and skeleton rows. Open tickets are the default; tickets waiting on the viewer are ordered first and get a "Perlu balasan Anda" badge next to the title.

Agents get **Antrean** (`/queue`) with five queues, live counts, bulk assign/status actions, priority/SLA columns and the same filter/pagination conventions.

### Where things live

| Concern | File |
| --- | --- |
| Sidebar + top bar + type picker | `resources/views/components/layout.blade.php` (via the `layouts/app.blade.php` shim) |
| Status presentation | `app/Enums/StatusCategory.php` → `<x-status-badge>` |
| Request-type catalogue | `config/request-types.php` + `app/Support/RequestTypeCatalog.php` |
| Requester filters | `app/Http/Requests/ListMyRequestsRequest.php` → `app/Support/Queries/MyRequestsQuery.php` |
| Agent queues | `app/Http/Requests/AgentQueueRequest.php` → `app/Support/Queries/AgentQueueQuery.php` |
| List components | `x-ticket-table`, `x-filter-bar`, `x-pagination`, `x-empty-state`, `x-priority-icon`, `x-avatar`, `x-request-type-card` |
| Feature tests | `tests/Feature/PortalUiTest.php` |

Query strings never reach Eloquent directly: each list has a FormRequest that folds unexpected values back to safe defaults, and a dedicated Query class. Ticket visibility is still enforced by `VisibleToUserScope` and `TicketPolicy`; agents are gated by `AgentQueueRequest` and the bulk-action FormRequest.

### Adding a new status

1. Add a case to `app/Enums/TicketStatus.php`.
2. Map it to one of the four categories in `TicketStatus::category()` (`todo`, `in_progress`, `waiting`, `done`). A category owns exactly one label, icon and colour, so duplicate labels with different icons cannot happen.
3. If you need a fifth category, add it to `app/Enums/StatusCategory.php` (label key, `tone()`, `iconPath()`) and add the matching `portal.status.*` strings to **both** `lang/id/portal.php` and `lang/en/portal.php`.
4. Add the plain status translation to `lang/{id,en}/status.php` if the new database value needs its own name in admin/workflow screens.

### Adding a request type

1. Add a key under `types` in `config/request-types.php` with its `group`, target `project` key, search `keywords` and an SVG path.
2. Add `portal.types.<key>.label` and `portal.types.<key>.description` to **both** `lang/id/portal.php` and `lang/en/portal.php`.
3. That's it — the type automatically appears on Beranda, in the "+ Buat" picker, in the list filter, in live search, on the create form (as a preselected type/project) and in the demo seeder. The value stored on the ticket is the config key in `tickets.type`.

To add a group, add it to `request-types.groups` and `portal.groups.*` in both locales.

## Documentation

- [Alur kerja per peran (diagram)](docs/ROLE-FLOW.svg)
- [UI craft rules](docs/DESIGN.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Security](docs/SECURITY.md)
- [Deploy](docs/DEPLOY.md)
- [Runbook](docs/RUNBOOK.md)
- [Admin guide](docs/ADMIN-GUIDE.md)
- [User guide](docs/USER-GUIDE.md)
- [Panduan penggunaan — 1 halaman, visual (Bahasa Indonesia)](docs/PANDUAN-PENGGUNAAN.html) — juga tersedia di aplikasi lewat sidebar **Panduan penggunaan** (`/panduan`)
- [Load test](docs/LOADTEST.md)
- [Changelog](docs/CHANGELOG.md)
