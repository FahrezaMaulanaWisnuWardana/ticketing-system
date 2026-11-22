# Go-live checklist

Evidence-oriented checklist. Items marked ⚠️ require verification on the actual production host (no Docker daemon or external services were available in the build environment).

## Verified in code/tests

- [x] `APP_DEBUG=false`, caches build (`config:cache`, `route:cache`, `view:cache`, `event:cache`) — verified locally.
- [x] Security headers middleware + nonce-based CSP; production compose sets `CSP_MODE=enforce` — verified by `SecurityHeadersTest`.
- [x] All Policies tested including IDOR matrix (tickets, attachments, Livewire payloads) — `AuthorizationTest`, `HelpdeskTest`.
- [x] Google domain/verified-email checks, inactive user blocked, invite expiry — `AuthTest`.
- [x] Upload validation, private storage, markdown/XSS sanitisation — `TicketLifecycleTest`, `ServicesTest`.
- [x] Queues (`notifications`, `reports`, `mail-ingest`, `default`), scheduler, Reverb services defined — `config/horizon.php`, Compose files.
- [x] Data retention + deactivation jobs scheduled and tested — `HardeningTest`.
- [x] Backup/restore drill executed 2026-11-22 against a scratch PostgreSQL database — see RUNBOOK.
- [x] Migrations reversible (`down()` on every migration) — verified with `migrate:rollback`.
- [x] i18n id/en key parity test, timezone display `Asia/Jakarta`, UTC storage.

## Requires production-host verification ⚠️

- [ ] HTTPS/HSTS at the edge, trusted proxies correct, secure cookies.
- [ ] Zero CSP violations in normal use (run report-only first on staging).
- [ ] Admin 2FA enrolment completed for every admin; seed accounts removed.
- [ ] Mail deliverability (SPF/DKIM/DMARC) and test notifications received.
- [ ] Horizon/scheduler/Reverb supervised with healthchecks and empty failed-job queue.
- [ ] Off-host encrypted backups, WAL/PITR if required, restore drill on production-sized copy.
- [ ] Host log alerting receives a test application error and notifies the on-call.
- [ ] k6 load targets met (`tests/load/load.js`) and recorded in LOADTEST.md.
- [ ] Five pilot users complete the five critical journeys without help; create ticket <10 s.
- [ ] Open known issues list reviewed and accepted by the owner.

## Optional phases

Phase 6b/6c were built after an explicit request. Verify on staging that `INTEGRATION_TRELLO_ENABLED=false` hides every Trello route/menu/job before go-live.
