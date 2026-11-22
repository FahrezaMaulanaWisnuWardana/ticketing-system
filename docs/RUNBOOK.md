# Runbook (on-call)

## Failed jobs

`php artisan horizon:failures` shows failures. Retry with `php artisan queue:retry <id>` or from Horizon. If more than 10 failed jobs, check the queue owner and alert channel.

## Stuck queue

`php artisan horizon:pause` → inspect → `php artisan horizon:terminate`. Restart the worker container. Verify with `php artisan queue:monitor redis:default,redis:notifications,redis:reports`.

## Reverb down

Check the `reverb` container, `php artisan reverb:restart`, and nginx `location /app/` upgrade headers. Clients refresh state on reconnect.

## Database restore

1. `pg_dump -Fc` current DB as a safety copy.
2. `scripts/restore-test.sh` with `SCRATCH_URL` first.
3. Restore into production during a maintenance window; `php artisan migrate --force`; rebuild caches.

## Deactivating a user

Admin → Users → Deactivate. This sets `is_active=false`, deletes Sanctum tokens and invalidates sessions on the next request. Reassign open tickets before offboarding.

## Rotating keys

`php artisan key:generate` sets a new `APP_KEY`; add the old key to `APP_PREVIOUS_KEYS` for one release, then remove. Rotating OAuth/Reverb secrets requires redeploying app, reverb and worker.

## Re-sending notifications

Queued notifications are jobs on the `notifications` queue. Re-dispatch from the originating action or retry the failed job in Horizon. Never send internal-note content to requesters.

## Common errors

| Error | Cause | Fix |
|---|---|---|
| `ViteManifestNotFoundException` | assets not built | `npm ci && npm run build` |
| `personal_access_tokens does not exist` | Sanctum migration not run | `php artisan migrate` |
| 403 on ticket detail | policy/scope denial | check role + project membership |
| 503 from `/health` | a probe failed | inspect JSON `checks` object |

Monthly restore drill: run `scripts/restore-test.sh` and record the result here.


## Alerts (wired hooks)

`CheckSystemHealthJob` runs every 5 minutes and writes `health.scheduler_at` and `health.failed_jobs` to settings. It emits log warnings when: failed jobs > 10, backup older than 26h (or never recorded), disk usage > 80%. Connect these log lines to your log alerting (PagerDuty/webhook/email) on the host. Add SSL-expiry and 5xx-rate alerts at the edge.

## Restore drill (executed 2026-11-22)

`pg_dump -Fc ticketing` → `scripts/restore-test.sh` with a scratch database. Extensions (`citext`, `pg_trgm`) are created before restore. Sanity queries on `users` and `tickets` succeeded. Repeat monthly and record the date here.

## Retention

`PruneOldDataJob` runs weekly: tickets older than 3 years are soft-deleted, audit rows older than 1 year and failed jobs older than 1 week are removed. Configure via `TICKET_RETENTION_YEARS` / `AUDIT_RETENTION_YEARS`.


## Trello integration

- Verify the flag: `INTEGRATION_TRELLO_ENABLED=false` disables all Trello routes/jobs/UI.
- Connection stuck in `error`: check `integration_logs` and `last_error`, fix credentials or wait out the rate window, then **Resume** (reconciliation is queued automatically).
- Webhook disabled: `CheckTrelloWebhooksJob` logs `webhook_unhealthy`; re-register by switching mode or reconnecting the board.
- Rate-limit storm: a 429 pauses the connection for the window; no manual action is needed unless it repeats.
- Rotating a leaked token: rotate in Trello, update the connection token, then disconnect the old connection with "forget links" if required.
- Conflicts: Project settings → Integrations lists every `app_wins` conflict; the app value was kept and Trello was overwritten.
