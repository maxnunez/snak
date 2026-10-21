# Proposal: Staff Authentication and Authorization

**Change**: `auth-roles` (change 2 of 8). Depends on `scaffold-ci4` (archived 2026-10-21).

## Intent

Students do not authenticate: they reserve on a public page with first name, surname and carnet, and the cashier validates identity at pickup. Staff are the only authenticated actors, so the system needs one `users` table, a login, and a gate in front of the cashier area. Change 1 shipped no identity, no CSRF and no authorization; without this, products, stock, cash and reports (changes 3, 6, 7, 8) are open to anyone.

## Scope

### In Scope

- `users`: `id`, unique `username`, `password_hash`, `role`, `active`, `last_login_at`, timestamps.
- Session login/logout, global CSRF on state-changing verbs, session ID regeneration, cookie hardening.
- Login throttling: 5 failures / 15 min per username + IP, generic error message.
- `staff` filter (authenticated + active + optional allowed-role argument). `/cashier/*` protected; `/`, `/login`, `/health` and static assets public.
- `php spark user:create`: prompted password, role choice, no committed credential.
- Spanish copy in `app/Language/es/Auth.php`.
- Feature tests: login success/failure, logout, role gate, inactive user, throttle.

### Out of Scope

Password reset by email, 2FA, SSO, remember-me, student accounts, admin UI, audit log, products, reservations, POS, reports.

## Capabilities

### New Capabilities

- `staff-auth`: credentials, login/logout, session and CSRF hardening, throttling, first-account command.
- `access-control`: role model, route gate, public/protected split.

### Modified Capabilities

- `app-bootstrap`: (a) "Libraries wired, unused" is relaxed for SweetAlert2 (login alerts); DataTables stays unused until change 3. (b) "Fresh migrate creates no domain tables" becomes "migrations are the only schema path; fresh `migrate --all` creates exactly the tables the changes define" — this change adds the first domain tables.

## Approach

**D1 — Purpose-built minimal auth, not Shield.** Shield v1.4.1 is rejected on concrete numbers:

| Criterion | Shield 1.4.1 | Purpose-built |
|---|---|---|
| Dependencies | adds `codeigniter4/settings ^2.1` (absent today) + lockfile diff | framework only; no lockfile change |
| Generated surface | 1 migration, 7 tables (`users`, `auth_identities`, `auth_logins`, `auth_token_logins`, `auth_remember_tokens`, `auth_groups_users`, `auth_permissions_users`) + `Auth.php` 521 + `AuthGroups.php` 117 + `AuthRoutes.php` 95 ≈ 920 lines | 2 tables, ~200 authored lines |
| Feature fit | identities, JWT, remember-me, permissions — all out of scope | one login field, one role column, one filter |
| Review budget | 920 lines that cannot split | 3 slices ≤400 |
| Test cost | seed groups, Shield registrar, override its filters | plain model + filter, direct assertions |

Shield's value is in the features this change excludes; its 7-table migration would land as one unsplittable PR after change 1 already needed a lockfile `size:exception`.

**D2 — `admin` exists now.** `role` constrained to `cashier|admin`; change 2 creates cashier accounts only, and `admin` grants nothing extra yet. Products/stock/reports need the split; adding it later costs a migration plus a re-audit of every gate.

**D3 — `username` is the only identifier; no email column.** Staff log in at a counter; email has no consumer until the out-of-scope reset flow.

**D4 — Security baseline.** `password_hash(PASSWORD_DEFAULT)` / `password_verify`, plus a dummy verify on unknown users against enumeration. `session()->regenerate(true)` on login, destroy on logout. Enable the `csrf` filter globally (`Config\Filters` comments it out today), keep `httponly` and `samesite=Lax`, set `cookie.secure=true` per-environment. Throttle counters live in a `login_attempts` table, not the cache: auditable and deterministic in tests.

## Impact on `app-bootstrap`

Consumes change 1's CI4 4.7.5 app, DB and `/health`, PHPUnit, base layout, empty `app/Database/Migrations/`, `Language/es/App.php`, `Config\Security::$csrfProtection='cookie'`. Modifies `Config/Filters.php` (csrf global, `staff` alias), `Config/Routes.php` (login/logout, cashier group), `Config/App.php` (`$language='es'`) and `.env.example` (`tests` DB group). `/health` is unchanged and stays public (non-sensitive). No other requirement changes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Database/Migrations/*_CreateUsers.php`, `*_CreateLoginAttempts.php` | New | schema |
| `app/Models/UserModel.php` | New | lookup; role/active not mass-assignable from web input |
| `app/Filters/StaffFilter.php` | New | session + active + role check |
| `app/Controllers/AuthController.php`, `CashierController.php` | New | login/logout; protected landing |
| `app/Views/auth/login.php`, `app/Views/cashier/index.php` | New | Spanish forms, SweetAlert2 errors |
| `app/Language/es/Auth.php` | New | all user-facing copy |
| `app/Commands/UserCreate.php` | New | prompted password |
| `app/Config/{Filters,Routes,App}.php`, `.env.example` | Modified | CSRF, routes, locale, tests DB |
| `tests/feature/AuthTest.php`, `StaffFilterTest.php` | New | five behaviors |
| `composer.json`, `composer.lock` | Unchanged | no new dependency |

## Delivery Slices

1. `feat(auth): add staff users and login-attempt schema` — migrations, model, `user:create`, tests (~280).
2. `feat(auth): add staff login, logout and throttling` — controller, view, language, CSRF/session config, tests (~350).
3. `feat(auth): gate cashier area by role` — filter, routes, placeholder, filter tests (~250).

Stacked to main, in order; each slice autonomously verifiable.

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| We own security-critical code | Med | password_hash, global CSRF, regeneration, dummy verify, dedicated tests |
| Feature tests need a writable DB; change 1 had none | Med | provision `snak_test` + `tests` group; tests fail loudly if absent |
| `cookie.secure=true` breaks `spark serve` over HTTP | Med | set per environment via `.env`; verify before production |
| Throttle becomes a lockout weapon | Low | username + IP key, 15-min window, generic message |
| Later Shield adoption needs a data migration | Low | map `users` → `auth_identities`; <10 accounts |

## Rollback Plan

Revert the three commits; `php spark migrate:rollback` drops `users` and `login_attempts` (nothing references them); routes return to the change-1 state (`/` and `/health`). No production data exists.

## Success Criteria

- [ ] `user:create` creates an active cashier with a prompted password; no credential or default password in tracked files.
- [ ] Valid credentials reach `/cashier`; wrong ones show a Spanish alert and create no session.
- [ ] Inactive user cannot log in; a user deactivated mid-session is rejected on the next request.
- [ ] Logout destroys the session; the back button does not restore access.
- [ ] `/cashier` without a session redirects to `/login`; `staff:admin` rejects a cashier.
- [ ] The 6th failure in 15 minutes is blocked even with the correct password.
- [ ] `POST /login` and `/logout` require a valid CSRF token; `vendor/bin/phpunit` passes; each slice ≤400 lines.
