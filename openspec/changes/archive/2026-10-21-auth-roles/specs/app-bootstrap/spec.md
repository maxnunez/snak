# Delta for app-bootstrap

**Change**: `auth-roles`
**Type**: MODIFIED only — no requirement is removed.

## MODIFIED Requirements

### Requirement: Environment and Secret Isolation

`.env.example` MUST be tracked with database `snak` (utf8mb4), timezone `America/La_Paz`, and a `tests` database group pointing at `snak_test`. `.env` and `.env.testing` MUST be gitignored; no credential MAY appear in tracked files. (Previously: only the `snak` group was required; no test database existed.)

#### Scenario: Fresh clone boots

- GIVEN a fresh clone without `.env`
- WHEN `.env.example` is copied to `.env` with local credentials
- THEN the app boots and reaches `snak`
- AND its timezone is `America/La_Paz`

#### Scenario: No secrets tracked

- WHEN tracked files are scanned for `.env` and credentials
- THEN `.env` is not in `git ls-files`
- AND no tracked file contains a credential

#### Scenario: Test database isolated

- GIVEN `.env.testing` supplies the `tests` group credentials
- WHEN the PHPUnit suite runs
- THEN it connects to `snak_test` and never to `snak`
- AND a missing test database fails the suite loudly instead of skipping

### Requirement: Migration Infrastructure

Schema MUST change only through CI4 migrations. `php spark migrate --all` MUST succeed on an empty database and create exactly the tables the active changes define — no more, no fewer. (Previously: a fresh migrate was required to create no domain tables.)

#### Scenario: Fresh migrate

- GIVEN an empty database with every migration registered
- WHEN `php spark migrate --all` runs
- THEN it succeeds
- AND the created tables match the migrations in `app/Database/Migrations/`
- AND only migration bookkeeping tables exist beyond those

#### Scenario: Rollback removes domain tables

- GIVEN the change's migrations are applied
- WHEN `php spark migrate:rollback` runs
- THEN `users` and `login_attempts` are dropped
- AND no application table remains

### Requirement: Base UI Shell with Local Assets

The base layout MUST use local Bootstrap 4, primary `#003366`, black text on white, and local SweetAlert2 plus DataTables. No CDN assets. SweetAlert2 MAY be invoked by feature code; DataTables MUST remain unused until change 3. (Previously: both libraries were required to be wired but unused.)

#### Scenario: Shell renders

- WHEN a base-layout page is requested
- THEN it returns 200 with Bootstrap from local `public/assets`
- AND no asset has an external origin

#### Scenario: Theme applied

- WHEN the shell renders
- THEN primary elements are `#003366`
- AND text is black on white

#### Scenario: Library usage boundary

- WHEN the shell and auth feature are inspected
- THEN SweetAlert2 assets load and login alerts may call them
- AND DataTables assets load but no feature code invokes them
- AND no external origin serves either library
