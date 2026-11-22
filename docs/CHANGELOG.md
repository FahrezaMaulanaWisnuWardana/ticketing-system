# Changelog

## Rich-text editor with file handling

- Replaced plain textareas for ticket descriptions and replies with a TipTap WYSIWYG editor (bold, italic, underline, strike, lists, quote, code, links).
- Files can be pasted, dragged and dropped, or picked from the editor toolbar; images are embedded inline and other files become links.
- Uploads reuse the private attachment pipeline and stay pending until the form saves, then are claimed by the new ticket/reply. Unclaimed uploads are pruned by the retention job.
- CommonMark now passes HTML through and HTMLPurifier is the single sanitization boundary, so both legacy Markdown and new editor HTML render safely.
- Split heavy libraries into on-demand chunks: the initial JS bundle dropped from ~630 kB to ~66 kB (200 kB → 25 kB gzip). TipTap loads only where an editor is rendered; Gantt, Sortable, Echo and Pusher load only on pages that use them.
- Fixed the user factory not setting `theme`, which broke authenticated tests after the theme column was added.

## Runtime UI/auth fixes

- Removed duplicate Alpine instance that raced Livewire and left the quick-create modal permanently visible; modal is now Livewire-rendered and closes reliably.
- Quick-create now uses `redirectRoute()` so created tickets land on the confirmation page.
- Moved the 2FA challenge outside `auth` middleware, eliminating a login/challenge redirect loop.
- Fixed Reverb host env for PHP broadcasting in Compose and switched dev sessions to the database driver to avoid CSRF 419s.

- Dev Compose exposes Postgres on host port 5433 to avoid colliding with a host PostgreSQL on 5432; Navicat should use 5433.

## Docker runtime fixes

- Added `postgresql-dev` so `pdo_pgsql` compiles in Alpine images.
- Skipped Composer scripts until `artisan` exists in the dev image.
- Required non-empty `DB_PASSWORD` in Compose so app and Postgres cannot disagree silently.
- Verified the full dev stack builds and all containers report healthy; `/login` and `/up` return 200.

## Docker packaging

- Production Dockerfile is now truly multi-stage (Node asset build, Composer deps, non-root runtime with FPM tuning).
- Dev image generates APP_KEY safely in the entrypoint and discovers packages at build time.
- Compose stacks add healthchecks, `init`, restart policies, resource limits, and optional `vite`/`minio` profiles.
- Production compose shares `storage` and `bootstrap/cache` volumes, uses a DB secret, and defers `config:cache` to deploy time.
- Added `.env.production.example`; README and DEPLOY docs updated.

## UI polish (emilkowalski/skills + Web Interface Guidelines)

- Explicit easing/property transitions, `:active` press feedback, reduced-motion and touch-hover gating.
- Modal/toast entrance from `scale(0.96)` with keyboard-trigger suppression; centered modal origin preserved.
- Locale-aware dates (`IntlDateFormatter`, absolute after 7 days), pluralized counts, tabular numerals, `translate="no"` identifiers.
- Skip link, live region for async notices, long-content wrapping, destructive confirmations, no mobile autofocus.
- Added `docs/DESIGN.md` with the applied rules.

## Phase 6b/6c — Trello integration (complete)

- Optional, flag-gated Trello module: encrypted connection settings, adapter/client with rate limiting, SSRF protection and replay-safe signed webhooks.
- One-time board import (cards, lists, labels, checklists, comments, attachments) with idempotency and per-card error reports; mirror mode and connect-later wizard.
- Limited two-way sync with per-field hashes, conflict matrix (app always wins), invalid-transition revert, loop prevention, deletions/archives, reconciliation and pause/resume.
- 93 tests with `Http::fake()` fixtures; Pint clean, Larastan level 8 clean.

## Phase 6 — Reports & integrations (complete)

- Burndown/velocity/status/assignee/overdue/SLA reports with SQL aggregates and 10-minute cache.
- Injection-safe streamed CSV exports, nightly sprint snapshots.
- Signed GitHub/GitLab webhooks with replay protection, queued parsing, git links and PR-merge transitions.
- Optional REST API, email-in and directory sync intentionally deferred (marked optional in the spec).

## Phase 7 — Production hardening (complete)

- CSP enforce mode in production compose; security checklist evidence and accessibility walkthrough.
- k6 load script, health/alert job, retention job, backup/restore drill executed locally.
- Go-live checklist with explicit evidence and remaining infrastructure verification items.
- 78 tests, Pint clean, Larastan level 8 clean.

## Phase 5 — Project management (complete)

- Epics, milestones, computed progress/health with cached metrics and observer invalidation.
- Timeline (frappe-gantt), workload view, template seeding of categories/labels/members.
- Helpdesk → dev ticket linking with back-link and automatic internal note on completion.
- 73 tests, Pint clean, Larastan level 8 clean.

## Phase 4 — Dev work (complete)

- Sprints (start/complete transactional with snapshots and carry-over), labels, estimates, saved filters.
- Realtime board with keyboard-friendly status moves and optimistic rollback; backlog drag ranking and sprint assignment.
- Dev list with status/assignee/label/search filters; `TicketMoved` broadcast on private project channels.
- 68 tests, Pint clean, Larastan level 8 clean.

## Phase 3 — SLA (complete)

- Pure `SlaCalculator` with business hours, holidays, weekends and 24/7 urgent handling.
- Due dates computed on create, recalculated on priority change, paused/resumed with `waiting_requester`.
- Every-minute scheduler job with idempotent `sla_events` and at-risk/breached notifications.
- Admin SLA settings and holiday management; computed SLA badges in ticket views.
- 63 tests, Pint clean, Larastan level 8 clean.

## Phase 2 — Helpdesk (complete)

- Requester portal with open/resolved filters, friendly statuses, conversation reply and reopen.
- Confirmation page with ticket key and expected first-response target.
- Agent queue with priority/SLA-style urgency ordering, `A` assign shortcut and canned replies.
- Workflow wired into replies: requester replying to `waiting_requester` resumes work.
- Queued notification pipeline (mail + database + broadcast) with 60s debounce, per-user preferences, watchers and @mentions; internal notes never reach requesters.
- PostgreSQL full-text search (title A, description B, comments C via trigger), trigram fuzzy title, exact key first, policy-scoped, paginated, debounced UI.
- 55 tests, Pint clean, Larastan level 8 clean, reversible migrations.

## Phase 1 — Foundation (complete)

- Laravel 13 / PHP 8.3 scaffold with pinned dependencies and lockfiles.
- Docker Compose dev + production image skeletons, GitHub Actions CI.
- Tailwind design system (button, input, badge, modal, table, empty state) and app shell.
- Google + local auth, invitations, password reset, TOTP 2FA, audit log.
- Roles, policies and IDOR tests; users admin; projects/members/categories.
- Tickets with atomic numbering, friendly keys, comments, activity, attachments, markdown sanitisation.
- i18n scaffolding (`lang/id`, `lang/en`), security headers, `/up` and `/health`.
- 38 Pest tests, Pint clean, Larastan level 8 clean, composer/npm audits clean.
