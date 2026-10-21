# Tasks: Staff Authentication and Authorization (`auth-roles`)

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Authored changed lines | ~1,345 + ~50 deletions ≈ 1,395 |
| Generated lines | 0 tracked (no vendor/lockfile diff; `build/`, `.phpunit.cache` untracked) |
| 400-line budget risk | **High** |
| Chained PRs recommended | **Yes** |
| Suggested split | PR 1 (1a+1b) → PR 2 (2a+2b) → PR 3 (3), stacked to main |
| Delivery strategy | ask-on-risk (not supplied) |
| Chain strategy | stacked-to-main (design D10) |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High

| Unit | Commit | Authored | Generated | PR | Base |
|------|--------|----------|-----------|----|------|
| test-DB isolation + schema + model | 1a | ~330 | 0 | PR 1 | `main` |
| `user:create` CLI | 1b | ~150 | 0 | PR 1 | PR 1 branch |
| login/logout + CSRF + i18n | 2a | ~390 | 0 | PR 2 | PR 1 branch |
| throttling + language test | 2b | ~225 | 0 | PR 2 | 2a branch |
| role gate + 403 | 3 | ~250 | 0 | PR 3 | 2b branch |

**Confirm**: 2a ships login without throttling (design D10). Accept stacked intermediate state, or take `size:exception`.

## Guard Rails

- Strict TDD: **RED** (test file + case) → **GREEN** (implementation) → **PROOF**. Config/schema tasks are exempt but name their proof.
- PROOF is `vendor/bin/phpunit [--filter X]` against `snak_test` only. No credential in any tracked file or output.

## Group 1a — `feat(auth): add staff users and login-attempt schema` (PR 1)

> **First work unit of the change.** Test-DB isolation lands before any migration, so the suite can never touch `snak`.

- [x] 1a.1 Baseline: `mysql -usnak -p snak -e 'SHOW TABLES; SELECT COUNT(*) FROM migrations;' > /tmp/snak-baseline.txt`. Read-only. PROOF: file non-empty.
- [x] 1a.2 Config-only: `app/Config/Database.php` `$tests` → MySQLi, `127.0.0.1`, `snak_test`, `DBPrefix ''`, `utf8mb4`, `numberNative false`. PROOF: `--filter AppConfigTest`.
- [x] 1a.3 Config-only: new `tests/bootstrap.php` (DotEnv loads `.env.testing`, then `vendor/.../Test/bootstrap.php`); `phpunit.dist.xml` `bootstrap` + `<server>` `database.tests.*` (no password). PROOF: full suite green; 1a.1 diff empty.
- [x] 1a.4 Config-only: `.env.example` add `database.tests.*` + `cookie.secure`; `.gitignore` add `.env.testing`. PROOF: `git check-ignore .env.testing`; `git ls-files | grep -E '^\.env'` → `.env.example` only.
- [x] 1a.5 Schema-only: `app/Database/Migrations/2026-10-21-100000_CreateUsers.php` (ENUM `cashier`,`admin`; unique `username`; `is_active`) + `2026-10-21-100100_CreateLoginAttempts.php` (composite + `attempted_at` index). PROOF: `CI_ENVIRONMENT=testing php spark migrate --all`.
- [x] 1a.6 RED `tests/database/UserModelTest.php::testCreateStaffStoresHashRoleAndNullLastLogin` → GREEN `app/Models/UserModel.php`, `app/Exceptions/ValidationException.php`, `app/Services/AuthService.php` (`createUser`, `hashPassword`), `app/Config/Services.php` (`auth`). PROOF: `--filter UserModelTest`.
- [x] 1a.7 RED `UserModelTest::testDuplicateUsernameRejectedByValidationAndIndex` → GREEN `is_unique[users.username]`. PROOF: filter.
- [x] 1a.8 RED `UserModelTest::testRoleOutsideEnumRejected` + `::testRoleAndIsActiveNotMassAssignable` → GREEN `$allowedFields` excludes `role`/`is_active`; `createStaff()`, `setActive()`. PROOF: filter.
- [x] 1a.9 RED `UserModelTest::testPasswordRoundTripAndSevenByteRejection` → GREEN 8–72 byte rule in `createUser()`. PROOF: filter.

## Group 1b — `feat(cli): add user:create command` (PR 1)

- [x] 1b.1 RED `tests/database/UserCreateCommandTest.php::testCreatesActiveCashierViaAskSeam` → GREEN `app/Commands/UserCreate.php` (`stty -echo`, `stream_isatty` guard, `service('auth')->createUser`). PROOF: `--filter UserCreateCommandTest`.
- [x] 1b.2 RED `UserCreateCommandTest::testDuplicateUsernameExitsNonZeroAndWritesNothing` → GREEN `ValidationException` → `$this->error()` + `EXIT_ERROR` before insert. PROOF: filter + unchanged `COUNT(*)` on `users`.
- [x] 1b.3 RED `UserCreateCommandTest::testSevenBytePasswordRejectedWithSpanishMessage` + `::testCapturedOutputHasNoPasswordOrHash` → GREEN output `User #<id> created with role <role>.` PROOF: filter; `grep -riE 'password|hash' writable/logs/` empty.
- [x] 1b.4 Proof-only: `git grep -nE "password[[:space:]]*=[[:space:]]*'[^']+'"` → no hits; `git ls-files | grep -E '^\.env'` → `.env.example` only.

## Group 2a — `feat(auth): add staff login and logout` (PR 2)

- [x] 2a.1 RED `tests/feature/AuthTest.php::testLoginRegeneratesSessionUpdatesLastLoginAndRedirects` + new `tests/_support/AuthTestTrait.php` (`continueSession`, `postWithCsrf`) → GREEN `AuthService::attempt()`, `app/Controllers/AuthController.php`, `app/Views/auth/login.php`, `app/Language/es/Auth.php`, `app/Config/Session.php` `regenerateDestroy = true`. PROOF: `--filter AuthTest`.
- [x] 2a.2 RED `AuthTest::testWrongPasswordShowsGenericAlertAndNoSession` → GREEN flash `Auth.loginFailed` + SweetAlert2 in `pageScripts`. PROOF: filter.
- [x] 2a.3 RED `AuthTest::testUnknownUserMatchesWrongPasswordStatusAndMessage` → GREEN `dummyHash()` fallback. PROOF: filter.
- [x] 2a.4 RED `AuthTest::testInactiveUserRefusedWithGenericMessage` → GREEN `(int) $user->is_active !== 1`. PROOF: filter.
- [x] 2a.5 RED `AuthTest::testLogoutDestroysSessionAndRejectsReplay` → GREEN `AuthController::logout` + `POST logout`; explicit `remove()` then `destroy()`. PROOF: filter.
- [x] 2a.6 RED `AuthTest::testMissingCsrfTokenThrowsOnPostLogin` + `::testInvalidCsrfTokenThrows` → GREEN `app/Config/Filters.php` globals `before => ['csrf']`. PROOF: filter.
- [x] 2a.7 RED `AuthTest::testIntendedUrlRestoredAfterLogin` → GREEN `pullIntendedUrl()` (`/` prefix, rejects `//`). PROOF: filter.
- [x] 2a.8 RED `AuthTest::testLoginPageRendersSweetAlertAssetsAndNoDataTableCall` → GREEN login view `section('pageScripts')` (app-bootstrap delta: SweetAlert2 used, DataTables still unused). PROOF: filter.
- [x] 2a.9 Config-only: `.env.example` document `cookie.secure` dev/prod. PROOF: `grep -n cookie.secure .env.example`.

## Group 2b — `feat(auth): throttle failed logins` (PR 2)

- [x] 2b.1 RED `tests/database/LoginThrottleServiceTest.php::testCountsFailuresInWindowAndAtBoundary` → GREEN `app/Services/LoginThrottleService.php` + 2-line wiring in `AuthService::attempt()`. PROOF: `--filter LoginThrottleServiceTest`.
- [x] 2b.2 RED `LoginThrottleServiceTest::testPurgeRemovesOlderThan24hButKeepsWindow` + `::testResetWindowLeavesOlderRows` → GREEN `purge()` before insert; window-scoped `resetWindow()`. PROOF: filter.
- [x] 2b.3 RED `AuthTest::testSixthAttemptBlockedEvenWithCorrectPassword` + `::testFailuresOlderThanWindowAllowed` (`Time::setTestNow`) → GREEN block check before lookup. PROOF: `--filter AuthTest`.
- [x] 2b.4 RED `tests/unit/AuthLanguageTest.php::testEveryAuthKeyReferencedByIdentifierResolves` (fails: `sessionExpired`, `forbidden` absent) → GREEN add both to `app/Language/es/Auth.php`. PROOF: `--filter AuthLanguageTest`.

## Group 3 — `feat(auth): gate cashier area by role` (PR 3)

- [x] 3.1 RED `tests/feature/StaffFilterTest.php::testAnonymousRequestRedirectsAndStoresIntendedUrl` → GREEN `app/Filters/StaffFilter.php`, `Config/Filters.php` alias `staff`, `Config/Routes.php` `staff:cashier` group, `app/Controllers/CashierController.php`, `app/Views/cashier/index.php`. PROOF: `--filter StaffFilterTest`.
- [x] 3.2 RED `StaffFilterTest::testCashierGetsCashierHome200` → GREEN `AuthService::currentUser()` (no memoisation). PROOF: filter.
- [x] 3.3 RED `StaffFilterTest::testStaffAdminArgumentReturns403WithSpanishCopy` (`withRoutes`) → GREEN `$arguments` check + `app/Views/errors/403.php`. PROOF: filter.
- [x] 3.4 RED `StaffFilterTest::testDeactivatedMidSessionDestroysSessionAndRedirects` → GREEN re-read row + `remove()`/`destroy()` + `lang('Auth.sessionExpired')`. PROOF: filter.
- [x] 3.5 RED `StaffFilterTest::testPublicRoutesRemainPublic` (`/`, `/login`, `/health`, static) → GREEN routes untouched. PROOF: filter.
- [x] 3.6 RED `StaffFilterTest::testRoleChangeTakesEffectOnNextRequest` → GREEN no role in session payload. PROOF: filter.
- [x] 3.7 **`snak` untouched**: run full suite, then `mysql -usnak -p snak -e 'SHOW TABLES; SELECT COUNT(*) FROM migrations;' | diff /tmp/snak-baseline.txt -`. PROOF: empty diff.
- [x] 3.8 Manual M3: `php spark migrate --all` on `snak` → exactly `migrations`, `users`, `login_attempts`; `migrate:rollback` drops both; re-migrate.

No code change needed in `Config/App.php` (locale already `es`), `Cookie.php`, `Security.php`, `composer.*` — verified, not tasks.

## Verify-Fix Batch — post-verify findings (2026-10-21)

- [x] VF.1 RED `StaffFilterTest::testDeactivatedMidSessionDestroysSessionExactlyOnceAndRedirects` + `AuthTest::testLogoutDestroysSessionExactlyOnceEvenIfCalledTwice` with `DestroyCountingSession` (fails on the second destroy) → GREEN `AuthService::logout()` idempotent guard and `StaffFilter` no longer destroys after `currentUser()`. PROOF: `--filter 'testDeactivatedMidSession...|testLogoutDestroysSession...'`; live deactivation → 302 `/login`, no new 500.
- [x] VF.2 RED `AuthTest::testUnknownUserRunsDummyPasswordVerification` → GREEN `AuthService::verifyPassword()` seam; unknown user verifies a dummy hash, known user verifies the stored hash. PROOF: filter.
- [x] VF.3 `AuthTest::testLogoutWithInvalidCsrfTokenKeepsSessionIntact` (characterization of the already-shipped CSRF filter). PROOF: filter.
- [x] VF.4 `AppConfigTest::testProductionCookieEnvironmentEnablesSecureHttpOnlyLax` (env override; dev may omit Secure). PROOF: filter.
- [x] VF.5 `AppConfigTest::testStaffFilterIsRegisteredAndAppliedToCashierRoutes` (alias + `staff:cashier` on the cashier group). PROOF: filter.
- [x] VF.6 RED `UserCreateCommandTest::testDuplicateUsernameExitsNonZeroAndWritesNothing` + `::testInvalidUsernameRejectedWithSpanishMessage` → GREEN Spanish `Auth.*` validation messages in `AuthService::createUser()`. PROOF: filter.
- [x] VF.7 `.env.example` test username is `snak`, matching `.env.testing` and `phpunit.dist.xml`; `AuthLanguageTest` scans commands, filters, and services. PROOF: `composer cs`; full suite.
- [x] VF.8 `snak` untouched: `users=0`, `login_attempts=0`, `migrations=2` before and after the suite plus the live check. PROOF: identical counts.

## Notice-Delivery Fix — re-verify warning 2 (2026-10-21)

- [x] VF.9 RED `StaffFilterTest::testDeactivatedMidSessionDestroysSessionAndShowsExpiredNoticeOnLogin` with `TeardownSession` (drops writes after `destroy()`, like a real session teardown) → fails on the post-destroy flash (`getCookie` null). GREEN: the filter sets a one-time `snak_auth_notice` cookie, `AuthController::showLogin()` consumes it and renders `lang('Auth.sessionExpired')`; one destroy, message shown once. PROOF: `--filter testDeactivatedMidSessionDestroysSessionAndShowsExpiredNoticeOnLogin` → 1 test, 10 assertions.
- [x] VF.10 Live proof (Apache `http://snak.local`): temp cashier login 303 → `/cashier`, active 200; deactivate → 302 `/login` + `Set-Cookie: snak_auth_notice=session_expired` (HttpOnly, SameSite=Lax); `GET /login` renders `Su sesión terminó. Ingrese nuevamente.`, second `GET /login` does not. Cleanup: temp user deleted, `snak.users=0`. PROOF: curl transcript; full suite 51/51 green on `snak_test`; `snak` counts unchanged.

## Manual / Operator-Only (never from apply)

| # | Step | Why |
|---|------|-----|
| M1 | Create `.env.testing` (`database.tests.username = snak` + password from `.env`) | secret; required by 1a.3 |
| M2 | Production `.env`: `cookie.secure = true` before exposing `/login` | deployment fact, not CI-assertable |
| M3 | `migrate --all` / `migrate:rollback` on `snak` (= 3.8) | mutates dev DB, not the suite |
| M4 | `php spark user:create`, password typed at prompt | no credential in files |
| M5 | `snak_test` grants + vhost/TLS | already verified; no sudo step in apply |
