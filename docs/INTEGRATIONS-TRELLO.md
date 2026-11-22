# Trello integration (Phase 6b/6c)

The app remains the source of truth. The integration is **disabled by default** (`INTEGRATION_TRELLO_ENABLED=false`); when disabled no Trello routes, jobs, menu entries or UI are registered.

## What is synced

| Direction | Fields | Notes |
|---|---|---|
| Import (once) | cards → dev tickets, lists → status, labels, due dates, checklists → sub-tasks, comments, attachments | Comments are prefixed `_Imported from Trello — name, date_`. Bad attachments are listed in the report. Internal notes, SLA, estimates, sprints and audit data are never imported. |
| Mirror (app → Trello) | title, description, due date, assignee name, status, public comments, deletion as archive | Helpdesk requests are excluded unless **Mirror helpdesk tickets** is enabled. Internal notes and SLA fields never leave the app. Each card has a footer link back to the ticket. |
| Webhook (Trello → app) | mirror mode: only a "changed on Trello" warning. two-way mode: title, description, due date, status, assignee, mapped labels, public comments | App wins all conflicts; rejected transitions move the card back and show a banner. Deleted/archived cards are unlinked, never deleted in the app. |

## Setup

1. Set `INTEGRATION_TRELLO_ENABLED=true`, `TRELLO_WEBHOOK_SECRET` and `TRELLO_CALLBACK_URL` in the environment, then redeploy.
2. In Project settings → Integrations, choose **Connect Trello**. Paste a **read-only, expiring** Trello token (prefer an expiring token for import-only) and the board ID.
3. Choose what happens to existing work: import cards, push project tickets to a board, link exact title matches, or start from now.
4. Choose mode: `import_only`, `mirror`, or `two_way`. Review the mapping, then confirm.
5. For mirror/two-way, a signed webhook is registered at `POST /webhooks/trello/{connection}`. Trello's verification request (`GET`/`HEAD`) is answered automatically.

Only admins and project leads/owners can connect, change mode, pause, resume, import or disconnect. Agents and requesters cannot see the integration UI.

## Limits and resilience

- `TrelloClient` is the only code that calls `https://api.trello.com/1/`. Connect timeout 5 s, total 15 s, retry on 429/5xx only.
- Fixed-window token buckets target at most 80% of Trello's documented limits (defaults: 240/key/10 s, 80/token/10 s; configurable). A 429 pauses the connection's queue for the rest of the window.
- Outbound jobs are queued on `integrations` (Horizon supervisor, max concurrency 2 by default), debounced 10 s per ticket, unique by ticket, 5 retries with backoff. After repeated failures the connection is marked `error`, the lead is alerted and further jobs stop until retry.
- Circuit breaker after 10 consecutive failures. Nightly reconciliation repairs missed/disabled webhooks within the same budget.
- Per-ticket write cap: 3 sync writes/minute, then backoff and a connection warning.

## Privacy & security

- Keys/tokens are encrypted at rest (`encrypted` cast), hidden from serialization and logs, and redacted from integration logs. Only the last 4 characters of the token are shown in the UI.
- SSRF: only `api.trello.com` is contacted; attachment downloads allow no redirects to other hosts.
- Imported content is untrusted: HTML is stripped on render, attachments are validated with the same rules as native uploads.
- Webhook deliveries are verified with HMAC-SHA256 over callback URL + body (constant-time compare) and replay-protected by action id for 24 h.

## Troubleshooting

| Symptom | Action |
|---|---|
| Connection `error` | Fix credentials/rate limit, then resume from Project settings; reconciliation runs on resume. |
| Webhook disabled | `CheckTrelloWebhooksJob` records `webhook_unhealthy`; re-register by switching mode or reconnecting. |
| Rate-limit storm | Queue pauses automatically; wait for the window, then resume. |
| Leaked token | Rotate in Trello, paste new token in settings, disconnect the old connection. |

Tests use `Http::fake()` with fixtures in `tests/Fixtures/trello/`; no real network calls are made.
