# access-control Specification

**Status**: Implemented
**Source change**: `auth-roles` (archived 2026-10-21)
**Verification**: PASS WITH WARNINGS (no CRITICAL)
**Test group**: `tests` (PHPUnit, database `snak_test`)

## Purpose

Role model and route gate separating public pages from the staff-only cashier area.

## Requirements

### Requirement: Role Model

Each staff account MUST hold exactly one role from `cashier` and `admin`. Both roles MUST be accepted by validation; any other value MUST be rejected. In this change only `cashier` accounts are provisioned, and `admin` grants no additional capability yet.

#### Scenario: Valid roles accepted

- GIVEN a staff record
- WHEN role is `cashier` or `admin`
- THEN the record is accepted

#### Scenario: Unknown role rejected

- GIVEN an arbitrary role string
- WHEN a staff record is created or updated
- THEN validation fails and no record is written

### Requirement: Authentication Gate

Every `/cashier/*` route MUST pass through the `staff` filter. An unauthenticated request MUST redirect to `/login` and record the intended URL in the session.

#### Scenario: Anonymous request redirected

- GIVEN no authenticated session
- WHEN `GET /cashier` is requested
- THEN the response redirects to `/login`
- AND the intended URL is stored for post-login redirect

#### Scenario: No protected content leaks

- GIVEN no authenticated session
- WHEN a protected route returns
- THEN the body contains no cashier markup or data

### Requirement: Role Authorization

The `staff` filter MUST accept an optional allowed-role argument and deny authenticated users whose role is not listed. A cashier MUST reach `/cashier/*`. In this change `/cashier/*` admits `cashier` only; `admin` is denied there until a later change defines admin surfaces.

#### Scenario: Cashier allowed

- GIVEN an active cashier is authenticated
- WHEN `GET /cashier` is requested
- THEN the response is 200

#### Scenario: Non-cashier forbidden

- GIVEN an authenticated user whose role is not permitted
- WHEN a role-gated route is requested
- THEN the response is 403
- AND the Spanish forbidden message is rendered
- AND no redirect loop occurs

#### Scenario: Admin argument rejects cashier

- GIVEN a route gated with `staff:admin`
- WHEN an authenticated cashier requests it
- THEN the response is 403

### Requirement: Active Status at the Gate

The gate MUST re-read `active` on every request. An authenticated user who is inactive MUST have the session destroyed and be redirected to `/login`.

#### Scenario: Deactivation takes effect

- GIVEN an authenticated cashier is deactivated after login
- WHEN the next protected request arrives
- THEN the session is destroyed and the response redirects to `/login`

### Requirement: Public Route Preservation

`/`, `/login`, `/health`, and static assets under `public/` MUST remain reachable without authentication. The login endpoints MUST be public while still enforcing CSRF on POST.

#### Scenario: Public pages stay public

- GIVEN no session
- WHEN `GET /`, `GET /login`, `GET /health`, or a static asset is requested
- THEN each returns its normal response without an auth redirect

#### Scenario: Login POST is public but guarded

- GIVEN no session
- WHEN `POST /login` carries a valid CSRF token
- THEN it is processed rather than redirected by the auth gate

### Requirement: Centralized Enforcement

Authorization MUST be enforced by the registered filter and route groups, not by ad-hoc session checks inside controllers. Role or active changes MUST take effect on the next request without re-login.

#### Scenario: Filter registered once

- WHEN `Config/Filters.php` and `Config/Routes.php` are inspected
- THEN the `staff` alias is registered and applied to the cashier group
- AND controllers contain no duplicate role checks

#### Scenario: Next-request effect

- GIVEN a role change applied directly to the account
- WHEN the next protected request arrives
- THEN the new role governs the response

## Non-Functional Requirements

- Every scenario above MUST have a PHPUnit test in the `tests` group against `snak_test`.
- Denials MUST return 403 for authenticated users and a redirect for anonymous ones; the two MUST NOT be interchanged.
- Error copy MUST come from language files in Spanish; no role or credential detail MAY appear in the response body.
