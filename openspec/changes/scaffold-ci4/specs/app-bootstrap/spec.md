# Delta for app-bootstrap

Change 1 only. Confirmed decisions (BOB minor units, `America/La_Paz`, cash + manual QR) bind later capabilities.

## ADDED Requirements

### Requirement: Composer-Installed CI4 Skeleton

The app MUST be a CodeIgniter 4.x Composer skeleton, PSR-12 in `app/`, serving only `public/`.

#### Scenario: Boot from public/

- GIVEN dependencies installed
- WHEN `php spark serve` starts the app
- THEN `GET /` returns 200 from `public/index.php`
- AND `app/`, `writable/`, `composer.json` are not HTTP-reachable
- AND the style check reports no PSR-12 violations

### Requirement: Environment and Secret Isolation

`.env.example` MUST be tracked with database `snak` (utf8mb4) and timezone `America/La_Paz`. `.env` MUST be gitignored and no credential MAY appear in tracked files.

#### Scenario: Fresh clone boots

- GIVEN a fresh clone without `.env`
- WHEN `.env.example` is copied to `.env` with local credentials
- THEN the app boots and reaches `snak`
- AND its timezone is `America/La_Paz`

#### Scenario: No secrets tracked

- WHEN tracked files are scanned for `.env` and credentials
- THEN `.env` is not in `git ls-files`
- AND no tracked file contains a credential

### Requirement: Health Endpoint

`GET /health` MUST return JSON with English keys and values: 200 when the DB is reachable, 503 when not, never exposing credentials or traces.

#### Scenario: DB reachable

- GIVEN MariaDB accepts the configured user
- WHEN `GET /health` is requested
- THEN it returns 200 with `{"status":"ok","db":"ok"}`

#### Scenario: DB unreachable

- GIVEN MariaDB is down or credentials are wrong
- WHEN `GET /health` is requested
- THEN it returns 503 with `db` failing
- AND the body leaks no DSN, username, password, or trace

### Requirement: Migration Infrastructure

Schema MUST change only through CI4 migrations. `php spark migrate` MUST succeed on empty `snak` and create no domain tables.

#### Scenario: Fresh migrate

- GIVEN an empty `snak` database
- WHEN `php spark migrate` runs
- THEN it succeeds
- AND only migration bookkeeping tables exist
- AND rolling back leaves no application table

### Requirement: Test Runner

PHPUnit MUST be a Composer dev dependency; `vendor/bin/phpunit` MUST pass with one smoke test; `openspec/config.yaml` MUST record it as `verify.test_command`.

#### Scenario: Suite passes

- WHEN `vendor/bin/phpunit` runs
- THEN it exits 0

#### Scenario: Smoke test catches broken boot

- GIVEN the smoke test asserts a successful response
- WHEN the app fails to boot
- THEN the suite fails

#### Scenario: Command recorded

- WHEN `openspec/config.yaml` is read
- THEN `test_command` is `vendor/bin/phpunit` and `runner_available` is true

### Requirement: Base UI Shell with Local Assets

The base layout MUST use local Bootstrap 4, primary `#003366`, black text on white, and SweetAlert2 plus DataTables wired but unused. No CDN assets.

#### Scenario: Shell renders

- WHEN a base-layout page is requested
- THEN it returns 200 with Bootstrap from local `public/assets`
- AND no asset has an external origin

#### Scenario: Theme applied

- WHEN the shell renders
- THEN primary elements are `#003366`
- AND text is black on white

#### Scenario: Libraries wired, unused

- WHEN the shell is inspected
- THEN SweetAlert2 and DataTables assets load
- AND no feature code invokes them

### Requirement: Language Rules

User-facing strings MUST come from CI4 Language files, Spanish for student and cashier views, including all later views. `/health` and all identifiers, schema, comments, and artifacts MUST be English.

#### Scenario: No hardcoded copy

- WHEN the base shell is reviewed
- THEN it holds no user-facing prose outside Language files

#### Scenario: Health is English

- WHEN `GET /health` responds
- THEN keys and values are English tokens (`ok`, `error`)

### Requirement: PHP 8.4 Compatibility

The app MUST declare PHP `>=8.4` and MUST boot and test on PHP 8.4 without fatal or deprecation errors.

#### Scenario: Clean run

- GIVEN PHP 8.4
- WHEN the app boots and the suite runs
- THEN no fatal or deprecation error appears
