# Admin guide

## Users

- **Invite:** Users → Invite. Enter name, email and role. The user gets a 48-hour signed set-password link by email (queued).
- **CSV import:** Users → Import users (CSV). Columns: `name,email,role`. The report lists created rows and per-row validation errors.
- **Roles:** `requester` (default), `agent` (IT/dev team), `admin` (everything). Project roles are `lead` and `member` and are set on the project page.
- **Deactivate:** blocks login and invalidates sessions immediately. Reactivate with the same action.
- **2FA reset:** clears a user's TOTP secret and recovery codes; they must re-enrol.

Admins must configure TOTP before using the app when `REQUIRE_ADMIN_2FA=true`.

## Projects and categories

Only agents/admins can create projects. A project needs a name and a 2–10 letter key; the chosen template seeds categories. Leads/owners can edit project details, members and categories.

Categories define the requester form. The `form_schema` is a JSON array of fields (`name`, `label`, `type`, `required`).

## Canned replies

Project leads/owners can add canned replies on the project page; agents pick them from a dropdown in the Helpdesk queue. Global replies (`project_id = null`) can be added by admins.

## Settings

Auto-close days and reopen window are admin-editable. SLA/business hours settings arrive in Phase 3.

## Audit log

All authentication, role, activation, invite and password events are recorded in an immutable audit log.

## SLA

Admin → SLA settings configures business hours, working days, the 24/7 urgent flag, response/resolve targets (business minutes) and holidays. SLA due dates are computed automatically and recalculated when priority changes.
