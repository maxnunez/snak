# Proposal: snak Foundation and Change Roadmap

**Change**: `scaffold-ci4` (change 1 of 8). One product, delivered in slices. This proposal fixes the delivery order; only change 1 is implemented here.

## Intent

UAB students pre-reserve snacks; the cashier also sells at the counter (POS) and collects payments. No software exists: `/var/www/html/snak` is empty and the `snak` database is provisioned but unused. Every later feature needs a bootable CI4 app, a DB connection, a test runner, and a shared UI shell. Delivering that first makes the rest TDD-able and reviewable in slices instead of one unreviewable PR.

## Scope

### In Scope

- CI4 4.x skeleton via Composer, PSR-12, served from `public/`.
- `.env` from a committed `.env.example`: DB `snak`, utf8mb4; credentials only in `.env` (gitignored).
- Git init + `.gitignore` (`vendor/`, `.env`, `writable/*`); work-unit commits.
- PHPUnit (`vendor/bin/phpunit`) + one smoke test; real `verify.test_command` in `openspec/config.yaml`.
- Base layout: Bootstrap 4, primary `#003366`, black text on white, locally served assets; SweetAlert2 and DataTables wired but unused.
- `/health` route (app + DB connectivity). Migration infrastructure with zero domain tables.

### Out of Scope

Auth, roles, products, reservation window, reservations, cash register, POS, reports, any domain table, production vhost/TLS, backups.

## Capabilities

### New Capabilities

- `app-bootstrap`: app starts, connects to MariaDB, exposes `/health`, runs PHPUnit, renders the base UI shell.

### Modified Capabilities

None.

## Change Roadmap (ordered)

| # | Change | Delivers | Depends on | Budget risk |
|---|--------|----------|------------|-------------|
| 1 | `scaffold-ci4` | CI4, env, test runner, UI shell, `/health` | — | Low |
| 2 | `auth-roles` | Student + cashier identity, login/logout, role gate, first cashier seed | 1 | Med |
| 3 | `products-catalog` | Products (photo, price, name, stock), CRUD, DataTables, stock increase | 2 | High → split |
| 4 | `reservation-window` | Cashier enables/disables window; student "not enabled yet" page | 2 | Low |
| 5 | `student-reservations` | Selection, auto total, UID `1234-JP`, stock decrement, low/zero-stock alerts | 2,3,4 | High → split |
| 6 | `cash-register-opening` | Daily opening + starting cash, guard before cash operations | 2 | Low |
| 7 | `pos-collections` | Reservation search, register payment, ad-hoc POS sale | 3,5,6 | High → split |
| 8 | `reports` | Daily/weekly/monthly collections | 7 | Med |

Changes 4 and 6 are independent of 3 after 2 and MAY run in parallel. Changes 3, 5, 7 SHOULD be split when the tasks forecast exceeds 400 changed lines.

## Approach

CI4 MVC: thin controllers delegate to services (`ReservationService`, `CashRegisterService`) that hold business rules; CI4 Models with Query Builder; schema only through migrations. Money stored as integer minor units, never floats. Stock decrement inside a DB transaction with `SELECT ... FOR UPDATE`. Reservation UID generated server-side (4 random digits + initials, uppercase, hyphen) and uniqueness-checked among open reservations. English identifiers, schema, comments, and artifacts; Spanish only in student/cashier views via CI4 `Language` files.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `composer.json`, `app/`, `public/`, `writable/` | New | CI4 scaffold |
| `.env.example`, `.env`, `.gitignore` | New | Config and secret isolation |
| `phpunit.xml`, `tests/` | New | Runner and smoke test |
| `app/Views/layouts/`, `public/assets/` | New | Base shell, `#003366` theme |
| `openspec/config.yaml` | Modified | Real `verify.test_command` |

## Open Questions

| # | Question | Default if unresolved | Blocks |
|---|----------|-----------------------|--------|
| Q1 | Student auth: UAB SSO, student ID + PIN, magic link, or name/ID with cashier verification at pickup? | Name + student ID, no password; cashier verifies at pickup | 2, 5 |
| Q2 | Payment methods: cash only, or card/transfer? | **DECIDED**: cash (`efectivo`) and QR, both recorded manually by the cashier; no online payment, no gateway integration | 7 |
| Q3 | Is cash close/withdrawal (`cierre de caja`, retiros) in scope, or opening only? | Opening only; closing is a later change | 6, 8 |
| Q4 | Currency, taxes, rounding? | **DECIDED**: BOB (Bs), no tax breakdown, money as integer minor units (centavos), 2 decimals in display | 3, 7, 8 |
| Q5 | Who may increase stock: cashier, admin, or both? | Cashier + admin role | 3 |
| Q6 | Pay online or at pickup? Expiry/no-show policy? | Pay at pickup; unpaid reservations expire end of day | 5, 7 |
| Q7 | Overselling policy when the last unit is reserved concurrently? | Reject the second request; no backorder | 5 |
| Q8 | Timezone for daily report boundaries? | **DECIDED**: `America/La_Paz` (UTC-4), fixed for Bolivia; no per-user override | 6, 8 |
| Q9 | Product photo storage and limits? | Filesystem under `writable/uploads`, MIME + size validation | 3 |
| Q10 | UID collision: 10,000 numeric combinations. Release on pickup? | Unique among open reservations; released when paid | 5 |

## Verification Map

| Requirement | Change | Verifier check |
|-------------|--------|----------------|
| App boots, DB reachable | 1 | `php spark serve`; `GET /health` 200 with DB `ok`; PHPUnit green |
| No secrets tracked | 1 | `.env` ignored; repo grep finds no DB password |
| Window enforced server-side | 4 | Window off → student POST rejected, not merely hidden UI |
| Auto cost and total | 5 | Server recomputes Σ price×qty; client total ignored |
| Stock decrement on reserve | 5 | DB assertion after POST; transaction rollback test |
| Alerts <5, blocked at 0 | 5 | Boundary tests at 5, 4, 0; zero-stock product absent from catalog |
| UID format `1234-JP` | 5 | Regex `^\d{4}-[A-Z][A-Z]$`; collision retry |
| Search by name/surname | 7 | Partial, case-insensitive, accent-tolerant query test |
| Register payment | 7 | Reservation → paid; double submit idempotent |
| POS sale without reservation | 7 | Sale + stock decrement + payment row |
| Stock increase | 3 | Adjustment persists and is audited |
| Daily/weekly/monthly reports | 8 | Fixture totals match ledger; timezone boundary cases |
| Register must be open | 6 | Cash op blocked with Spanish warning; opening stores start amount |
| i18n rule | all | Views/alerts Spanish; identifiers, schema, artifacts English |
| Review budget | all | Each PR ≤400 changed lines or split |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| CI4 install/version drift on empty repo | Med | Pin a 4.x minor; smoke test in change 1 |
| DB credentials/vhost unverified | Med | `/health` proves connectivity before feature work |
| Strict TDD unexecutable until PHPUnit exists | High | Change 1 installs runner; `sdd-apply` MUST stop if missing |
| Scope exceeds 400-line budget | High | Roadmap slices; split 3, 5, 7 |
| Stock overselling | Med | Transaction + row lock + test |
| Spanish/English rule drift | Med | Language files only; grep check at verify |

## Rollback Plan

Change 1 is additive on an empty tree: revert the single commit and remove `vendor/`, `app/`, `public/`, `writable/`. No data or schema to undo (zero migrations). Later changes roll back per change via migration `down()` plus commit revert; no production data exists yet.

## Dependencies

- PHP 8.4.26, Composer 2.10, MariaDB 11.8, `snak` DB and user (verified active).
- CI4 4.x (latest stable) installable via Composer.
- Decisions on Q1–Q10 before changes 2, 3, 5, 6, 7, 8.

## Success Criteria

- [ ] `vendor/bin/phpunit` runs and passes; `config.yaml` `verify.test_command` updated.
- [ ] `/health` returns 200 with DB connectivity.
- [ ] Base layout renders `#003366` with Bootstrap 4 served locally.
- [ ] `.env` untracked; no credential in any committed file.
- [ ] Git repo initialized with work-unit commits and a clean `git status`.
