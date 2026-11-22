# Security

## Implemented (Phase 1)

- **Auth:** Google OAuth with server-side email-domain allowlist and `email_verified` check; `hd` is never trusted. Local accounts are admin-created and use 48h signed invite links; passwords require 12+ chars and `uncompromised()`. No public registration.
- **Session:** HTTP-only, SameSite=Lax, regenerate on login, 8h idle configurable. Deactivation middleware logs the user out on the next request and deletes tokens.
- **2FA:** RFC 6238 TOTP (6 digits/30s) with ±1 step window, hashed recovery codes, rate-limited verification, enforced for admins via `REQUIRE_ADMIN_2FA`.
- **Authorization:** Policies for Ticket, Project, Comment, User, Category, Attachment and AuditLog; requester global ticket scope; explicit IDOR tests for tickets, attachments and Livewire payloads.
- **Input:** FormRequests for all routes; `$fillable` everywhere; no raw SQL concatenation.
- **Output:** `{{ }}` by default. Only `MarkdownService::render()` returns trusted HTML, and it strips raw HTML, blocks `javascript:` URLs and adds `rel="noopener noreferrer nofollow"`.
- **Uploads:** private disk only; served by an authorized controller; real MIME via `finfo`; extension/mime allowlist; blocked executables/scripts; random names; SHA-256; `X-Content-Type-Options: nosniff`.
- **Headers:** HSTS (secure requests), `nosniff`, `X-Frame-Options: DENY`, Referrer-Policy, Permissions-Policy and a nonce-based CSP (report-only by default, enforce in Phase 7).
- **Audit:** login success/failure, role changes, activation, invites, password changes and 2FA events in immutable `audit_logs`.
- **Throttles:** login, password reset, invite resend, comments, uploads, webhooks.

## Pen-test checklist (to repeat before go-live)

1. **IDOR matrix:** requester A → ticket/attachment/comment/Livewire payload of B → 403/404.
2. **XSS payloads** in title, description, comment, category form values, file names:
   `<script>alert(1)</script>`, `<img src=x onerror=alert(1)>`, `javascript:alert(1)`, `[x](javascript:alert(1))`, `"'><svg/onload=alert(1)>`.
3. **Upload bypass:** `.php`, `.php5`, `.phtml`, `.exe`, `.svg`, double extensions, mismatched client MIME, polyglot files >10 MB.
4. **Rate limits:** 6 logins/min, 4 resets/hour, webhook floods.
5. **Auth:** unverified Google email, disallowed domain, reused google_id, expired invite, inactive account.
6. **Headers/CSP:** no violations in normal use once enforced.

Secrets live only in env/secret manager; `.env` is ignored. Logs redact password, token, secret, API keys and 2FA secrets. `APP_DEBUG=false` and config/route/view/event caches are required in production.


## Phase 7 evidence walkthrough

- **XSS:** payloads in title/description/comments/category values/file names are stripped by `MarkdownService`; assertions in `ServicesTest` and `HelpdeskTest`.
- **Upload bypass:** blocked extension validation in `StoreAttachmentRequest` + real MIME check in `AttachmentService`; private disk with authorized controller.
- **IDOR:** `AuthorizationTest` covers cross-user ticket URLs, attachments and tampered Livewire IDs (403/404).
- **CSRF:** all web state-changing routes use web middleware; `/webhooks/git/*` excluded and protected by HMAC/token checks.
- **Secrets:** `.env` ignored, Docker secrets for DB password in prod compose, no credentials in responses or logs; webhook secret redaction documented.
- **Accessibility:** keyboard-only paths verified for create (`C`), search (`/`), assign (`A`), status (`S`) and help (`?`); board provides a `<select>` move menu as a drag-and-drop alternative; labels and focus rings in the design system. Manual screen-reader pass still recommended before go-live.
