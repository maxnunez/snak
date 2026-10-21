# Verification Report — `auth-roles` (re-verify after verify-fix batch)

> Materialized into the archive from Engram observation #221 (`sdd/auth-roles/verify-report`).
> Only the `Previous verdict` line was corrected (the Engram copy referenced its own
> observation id); the report body is otherwise verbatim.
>
> **Archive note (2026-10-21)**: WARNING 2 (session-expired flash undeliverable) was
> resolved after this report by commit `d4e68f2` (`fix(auth): deliver session-expired
> notice after session teardown`) with VF.9/VF.10 evidence: a one-time
> `snak_auth_notice` cookie plus `AuthController::showLogin()` consumption, 51 tests /
> 241 assertions green, and a live deactivation showing the Spanish notice once. The
> verdict below stands: PASS WITH WARNINGS.

**Change**: auth-roles (`/var/www/html/snak`, CI4 4.7.5)
**Mode**: Strict TDD (runner `vendor/bin/phpunit`; test DB `snak_test`)
**Verified at**: 2026-10-21 (re-run)
**Previous verdict**: FAIL (earlier verify run; superseded by this report)
**Verdict**: **PASS WITH WARNINGS**

## 1. Completeness

| Item | Result | Evidence |
|---|---|---|
| Tasks checked | 42/42 | `grep -c '^- \[x\]' openspec/changes/auth-roles/tasks.md` → 42; unchecked → 0 |
| VF tasks | 8/8 | VF.1–VF.8 all `[x]` (`tasks.md:90-97`) |
| Commits | 9 conventional | 5 feature + 4 verify-fix, `4c2bca8..HEAD`; all `feat:`/`fix:`/`test:`/`docs:` |
| AI attribution | none | `git log -9 --format=%B \| grep -iE 'co-authored-by\|generated with\|claude\|openai\|copilot\|chatgpt\|assistant'` → no hits; trailers → none |
| Working tree | same as pre-verify | 4 deleted `openspec/changes/scaffold-ci4/*`; 5 untracked entries (auth-roles design/proposal/specs, archive, `openspec/specs/app-bootstrap`) |
| Remote | none | `git remote` → 0; everything is on local `main` |

## 2. Build & tests (re-executed)

**Tests**: ✅ 51 passed / 0 failed / 0 skipped — `vendor/bin/phpunit` exit 0
```text
OK (51 tests, 237 assertions) — 13.920 s
```
Second run for isolation check: `OK (51 tests, 237 assertions)` in 16.711 s.

**Style**: ✅ `composer cs` exit 0
```text
Found 0 of 75 files that can be fixed in 1.070 seconds
```

**Coverage** (manual run, Xdebug): `XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text` → OK, 51 tests. Changed core files:
| File | Lines | Rating |
|---|---|---|
| `app/Services/AuthService.php` | 100.00% (73/73) | ✅ |
| `app/Filters/StaffFilter.php` | 100.00% (21/21) | ✅ |
| `app/Models/UserModel.php` | 100.00% (17/17) | ✅ |
| `app/Services/LoginThrottleService.php` | 100.00% (24/24) | ✅ |
| `app/Controllers/AuthController.php` | 100.00% (39/39) | ✅ |
| `app/Controllers/CashierController.php` | 100.00% (3/3) | ✅ |
| `app/Commands/UserCreate.php` | 28.57% (10/35) | ⚠️ interactive prompt paths (untested by design) |

Aggregate: Lines 59.39% (234/394), Methods 71.74%, Classes 64.71%.

## 3. DB isolation (`snak` untouched)

Counts taken immediately before and after a full suite run:
```text
before: migrations=2 users=0 login_attempts=0 ; tables login_attempts,migrations,users
after : migrations=2 users=0 login_attempts=0 ; tables login_attempts,migrations,users
diff → empty
```
`snak_test` after suite: `users=1`, `migrations=2`, `login_attempts=0`. The suite wrote only to `snak_test`.

## 4. CRITICAL-1 re-test: mid-session deactivation (live, Apache `http://snak.local`)

Created temp `verifytemp` (cashier), logged in with a real CSRF round-trip, then set `is_active=0`:

| Step | Result |
|---|---|
| `POST /login` | 303 → `/cashier` |
| `GET /cashier` before deactivation | 200 |
| Log lines `Trying to destroy uninitialized session` before | **2** (historical, 09:12 and 09:18) |
| `GET /cashier` after deactivation | **302 → `/login`** (not 500) |
| Same log lines after | **2** (no new line) |
| Second protected request | 302 → `/login` |
| Log lines after a second deactivation run | 2 |

Cleanup: temp user and attempts deleted; final `snak`: `users=0`, `login_attempts=0`, `migrations=2`. **CRITICAL-1 is resolved.**

Residual UX finding: `StaffFilter.php:45` calls `->with('authError', lang('Auth.sessionExpired'))` *after* `AuthService::logout()` already destroyed the session (`AuthService.php:129-140`). Live: after the 302 to `/login`, `GET /login` shows **no** "Su sesión terminó" text (flashdata cannot survive the destroy). The test assertion `StaffFilterTest.php:99` (`assertSessionHas('authError', ...)`) passes only because the session double does not clear `$_SESSION`, so it is not production-faithful. Spec does not require the message → WARNING, not CRITICAL.

## 5. Regression sensitivity of `DestroyCountingSession` (proven in /tmp)

`tests/_support/DestroyCountingSession.php:22-28` counts `destroy()` and throws `RuntimeException('session_destroy(): ...')` on call #2. The test injects it through `Services::injectMock('session', ...)` (`AuthTestTrait.php:41-49`), so `service('session')` inside `AuthService`/`StaffFilter` resolves to the double.

Proof: copied the repo to `/tmp/snak-proof`, reintroduced the old double-destroy in the copy:
```php
if ($user === null) {
    $session->remove(AuthService::SESSION_USER_ID);
    $session->destroy();               // second destroy
    return redirect()->to('/login')->with(...);
}
```
`vendor/bin/phpunit --filter testDeactivatedMidSessionDestroysSessionExactlyOnceAndRedirects` → **ERROR, 0 assertions** with the exact production message, stack `DestroyCountingSession.php:27 → StaffFilter.php:46`. The test **does catch** the regression. Logic also holds for `AuthTest::testLogoutDestroysSessionExactlyOnceEvenIfCalledTwice` (second `logout()` would hit a second `destroy()`). Repository was not modified; `/tmp` copy deleted afterwards.

## 6. Meaningfulness of the new tests (no tautologies)

| Test | Verdict | Reason |
|---|---|---|
| `AuthTest::testLogoutWithInvalidCsrfTokenKeepsSessionIntact` (`AuthTest.php:159`) | ✅ Meaningful | Posts an invalid token, expects `SecurityException`, then asserts `user_id` unchanged and `/cashier` still 200. Exercises the real global CSRF filter. |
| `AuthTest::testUnknownUserRunsDummyPasswordVerification` (`AuthTest.php:178`) | ✅ Meaningful | `RecordingAuthService` records the hash passed to `verifyPassword()`; asserts exactly one verification with a hash **≠** the stored hash for `ghost`, and exactly the stored hash for the known user. Not a tautology. |
| `AppConfigTest::testProductionCookieEnvironmentEnablesSecureHttpOnlyLax` (`AppConfigTest.php:37`) | ✅ Meaningful | Overrides `$_ENV['cookie.secure']`; asserts dev `secure=false`, prod `secure=true`, plus `httponly=true` and `samesite=Lax`. `BaseConfig::initEnvValue()` (`BaseConfig.php:245-248`) is what maps the env key, and `Session::__construct()` consumes `Config\Cookie` (`Session.php:67-76`), so this covers the production path. |
| `AppConfigTest::testStaffFilterIsRegisteredAndAppliedToCashierRoutes` (`AppConfigTest.php:73`) | ✅ Meaningful | Asserts alias `staff → StaffFilter::class`, global `csrf`, and `getRoutesOptions('cashier','GET') === ['staff:cashier']`. Directly encodes the "filter registered once" scenario. |
| `StaffFilterTest::testDeactivatedMidSessionDestroysSessionExactlyOnceAndRedirects` (`StaffFilterTest.php:88`) | ✅ Meaningful (except flash assertion) | Asserts redirect, `user_id` null, `destroyCalls === 1`; the `authError` assertion is not production-faithful (see §4). |
| `UserCreateCommandTest::testInvalidUsernameRejectedWithSpanishMessage` | ✅ Meaningful | Asserts exit code, Spanish `Auth.usernameInvalid` in output, and `users=0`. |
| `AuthLanguageTest` scan | ✅ Meaningful | No manual key list; scans commands/controllers/filters/services/views. Verified all 7 files with `lang('Auth.*')` are in scope and all keys resolve. |

No tautologies (`assertTrue(true)` etc.), no ghost loops, no production-free assertions found across the 7 touched test files. Assertion counts: Auth 54/18 tests, StaffFilter 19/6, AppConfig 17/5, UserModel 27/5, Throttle 13/5, UserCreate 20/6, Language 4/1.

## 7. TDD compliance (Strict TDD)

| Check | Result | Details |
|---|---|---|
| TDD Evidence reported | ✅ | Table exists in apply-progress #219 under "### TDD Cycle Evidence", with rows for 1a/1b/2a/2b/3 **and** VF.1–VF.8 |
| All tasks have tests | ✅ | 42/42 tasks; every feature slice maps to an existing test case |
| RED confirmed (tests exist) | ✅ | All named test methods exist (checked by name); VF.1 RED independently reproduced in `/tmp` |
| GREEN confirmed | ✅ | 51/51 pass on execution |
| Triangulation adequate | ✅ | Two-case boundaries for length, throttle, CSRF, intended URL, role gate; single-case only where the spec has one scenario |
| Safety Net for modified files | ⚠️ | Table has no SAFETY NET / REFACTOR columns; no pre-change baseline documented for modified `AuthTest`, `StaffFilterTest`, `AppConfigTest`, `AuthLanguageTest`, `UserCreateCommandTest` |
| Assertion quality | ✅ | No trivial assertions; see §6 |

**Truthfulness**: VF rows match reality. VF.3/VF.4/VF.5 are honestly labelled "characterization" instead of claiming a RED phase. VF.1's RED claim is proven. One documentation error: the "Test Summary" layer distribution says Unit 5 / Feature 30 / DB 15, but actual counts are **Unit 6 / Feature 28 / DB 16** (+1 pre-existing = 51). Total 51 is correct.

### Test layer distribution (actual)
| Layer | Tests | Files |
|---|---|---|
| Unit | 6 | AppConfigTest (5), AuthLanguageTest (1) |
| Feature/integration (HTTP) | 28 | AuthTest (18), StaffFilterTest (6), HealthTest (2), ShellTest (2) |
| DB/integration | 16 | UserModelTest (5), UserCreateCommandTest (6), LoginThrottleServiceTest (5) |
| Pre-existing | 1 | ExampleSessionTest |
| **Total** | **51** | **10 files** |

## 8. Spec compliance matrix

### staff-auth — requirement-level: PASS
| Requirement | Scenario | Test / Evidence | Result |
|---|---|---|---|
| Staff Credential Storage | Staff record persists | `UserModelTest::testCreateStaffStoresHashRoleAndNullLastLogin` | ✅ PASS |
| | Duplicate username rejected | `::testDuplicateUsernameRejectedByValidationAndIndex` | ✅ PASS |
| Password Hashing Policy | Round-trip verification | `::testPasswordRoundTripAndSevenByteRejection` | ✅ PASS |
| | Length policy enforced | same test (7/73 byte) + `UserCreateCommandTest::testSevenBytePasswordRejectedWithSpanishMessage` | ✅ PASS |
| Login Success | Cashier logs in | `AuthTest::testLoginRegeneratesSessionUpdatesLastLoginAndRedirects`; live 303→`/cashier` | ✅ PASS |
| | Intended URL restored | `::testIntendedUrlRestoredAfterLogin` (+ unsafe URL fallback) | ✅ PASS |
| Login Failure Without Enumeration | Wrong password | `::testWrongPasswordShowsGenericAlertAndNoSession` | ✅ PASS |
| | Unknown user indistinguishable | `::testUnknownUserMatchesWrongPasswordStatusAndMessage` + `::testUnknownUserRunsDummyPasswordVerification` (dummy hash ≠ stored hash) | ✅ PASS |
| Inactive User Rejection | Inactive at login | `::testInactiveUserRefusedWithGenericMessage` | ✅ PASS |
| | Deactivated mid-session | `StaffFilterTest::testDeactivatedMidSessionDestroysSessionExactlyOnceAndRedirects`; live 302, 2→2 log lines | ✅ PASS |
| Logout | Logout destroys session | `AuthTest::testLogoutDestroysSessionAndRejectsReplay` + `::testLogoutDestroysSessionExactlyOnceEvenIfCalledTwice` | ✅ PASS |
| Login Throttling | Sixth attempt blocked | `::testSixthAttemptBlockedEvenWithCorrectPassword` | ✅ PASS |
| | Window expires | `::testFailuresOlderThanWindowAllowed` | ✅ PASS |
| Session & Cookie Hardening | Secure flags per environment | `AppConfigTest::testProductionCookieEnvironmentEnablesSecureHttpOnlyLax` (dev false; prod true; HttpOnly; Lax) | ✅ PASS |
| CSRF on Auth Endpoints | Missing token rejected | `AuthTest::testMissingCsrfTokenThrowsOnPostLogin` + `::testInvalidCsrfTokenThrows` | ✅ PASS |
| | Logout token required | `AuthTest::testLogoutWithInvalidCsrfTokenKeepsSessionIntact` | ✅ PASS |
| First Cashier Account Command | Creates an active cashier | `UserCreateCommandTest::testCreatesActiveCashierViaAskSeam` | ✅ PASS |
| | Duplicate refused | `::testDuplicateUsernameExitsNonZeroAndWritesNothing` (+ Spanish message) | ✅ PASS |
| | No credential in repository | Manual: `git ls-files` → only `.env.example`; DB password in 0 tracked files / 0 commits; no seeds | ⚠️ PASS (manual; NFR wants a PHPUnit test) |
| Spanish Copy via Language Files | Keys resolve | `AuthTest::testLoginPageRendersSweetAlertAssetsWithoutDataTableCall` | ✅ PASS |
| | Key presence asserted | `AuthLanguageTest::testEveryAuthKeyReferencedByIdentifierResolves` | ✅ PASS |

### access-control — requirement-level: PASS
| Requirement | Scenario | Test / Evidence | Result |
|---|---|---|---|
| Role Model | Valid roles accepted | `UserModelTest` (cashier) + `UserCreateCommandTest::testCreatesAdminWhenRoleSelected` | ✅ PASS |
| | Unknown role rejected | `UserModelTest::testRoleOutsideEnumRejected` (validation + ENUM) | ✅ PASS |
| Authentication Gate | Anonymous request redirected | `StaffFilterTest::testAnonymousRequestRedirectsAndStoresIntendedUrl`; live 302 | ✅ PASS |
| | No protected content leaks | same test asserts no `cashierHome`; live 302 body empty | ✅ PASS |
| Role Authorization | Cashier allowed | `::testCashierGetsCashierHome200`; live 200 | ✅ PASS |
| | Non-cashier forbidden | `::testRoleChangeTakesEffectOnNextRequest` (admin → 403) | ✅ PASS |
| | Admin argument rejects cashier | `::testAdminArgumentRejectsCashierWith403AndSpanishCopy` | ✅ PASS |
| Active Status at the Gate | Deactivation takes effect | `::testDeactivatedMidSessionDestroysSessionExactlyOnceAndRedirects`; live 302 | ✅ PASS |
| Public Route Preservation | Public pages stay public | `::testPublicRoutesRemainPublic` (`/`, `/login`, `/health`) | ✅ PASS |
| | Login POST public but guarded | `AuthTest::testLoginRegenerates...` (valid CSRF POST processed) | ✅ PASS |
| Centralized Enforcement | Filter registered once | `AppConfigTest::testStaffFilterIsRegisteredAndAppliedToCashierRoutes`; no controller role checks | ✅ PASS |
| | Next-request effect | `StaffFilterTest::testRoleChangeTakesEffectOnNextRequest` | ✅ PASS |

### app-bootstrap — requirement-level: PASS
| Requirement | Scenario | Test / Evidence | Result |
|---|---|---|---|
| Environment and Secret Isolation | Fresh clone boots | `AppConfigTest` (timezone `America/La_Paz`, `tests` group) + live boot; no real clone test | ⚠️ PARTIAL |
| | No secrets tracked | Manual: `.env`/`.env.testing` gitignored, only `.env.example` tracked; password in 0 files/commits | ⚠️ PASS (manual) |
| | Test database isolated | `AppConfigTest::testTestsDatabaseGroupPointsAtIsolatedMySqlDatabase` + row-count diff (snak unchanged) | ⚠️ PARTIAL (config asserted; runtime manual) |
| Migration Infrastructure | Fresh migrate | Manual `php spark migrate --all`; suite also applies migrations to `snak_test` | ⚠️ PARTIAL (no exact-table assertion) |
| | Rollback removes domain tables | Manual `migrate:rollback --all` → only `migrations` | ⚠️ PASS (manual) |
| Base UI Shell | Shell renders | `ShellTest::testHomeRendersBaseShellWithLocalAssets` | ✅ PASS |
| | Theme applied | `content="#003366"` asserted | ✅ PASS |
| | Library usage boundary | SweetAlert2 used; `DataTable(` absent | ✅ PASS |

**Compliance summary**: 35/41 scenarios directly covered by passing tests, 3/41 PARTIAL (manual/config-level), 3/41 manual-only, **0 FAILING**. All 20 requirements PASS.

## 9. Design coherence

| Decision | Followed? | Notes |
|---|---|---|
| `AuthService` single owner of session termination; idempotent logout | ✅ | `AuthService.php:129-140` marker guard; `StaffFilter.php:40-46` no longer destroys |
| Re-read role/active on every request | ✅ | `currentUser()` `AuthService.php:146-163`; no role in session |
| Cookie flags from env (`cookie.secure`) | ✅ | `Config/Cookie` env init + `Session.php:67-76` |
| Global CSRF + `staff:cashier` route group | ✅ | `Config/Filters.php`, `Config/Routes.php:13`; asserted |
| Spanish copy via `Auth.*` keys | ✅ | All 7 referencing files scanned; no literals |
| `is_active` for spec prose `active` | ✅ | Documented in design |
| `UserCreate::$group = 'snak'` | ❌ | `app/Commands/UserCreate.php:20` still `'auth'` (design.md:48 says `'snak'`) — cosmetic SUGGESTION |

## 10. Issues

### CRITICAL
None. CRITICAL-1 (mid-session 500), CRITICAL-2 (missing TDD table) and the code-adjacent part of CRITICAL-3 (logout CSRF, dummy verify, cookie flags, filter wiring) are resolved and re-proven.

### WARNING
1. **PR sizes still exceed the 400-line review budget** (`git diff --numstat`):
   | Slice | Raw add+del | Excl. `openspec/` |
   |---|---|---|
   | PR1 (1a+1b) | 892 (851+41) | 796 |
   | PR2 (2a+2b) | 864 (844+20) | 838 |
   | PR3 (3) | 272 (260+12) | 256 |
   | Verify-fix batch | 268 (253+15) | 257 |
   All three PR slices are ~2× budget; `size:exception` or actual stacked PRs is still required. No remote exists, so all 9 commits sit on local `main`.
2. **Session-expired flash is undeliverable in production** — `StaffFilter.php:45` writes `authError` after `AuthService::logout()` destroyed the session; live `GET /login` after deactivation shows no message. `StaffFilterTest.php:99` masks this because `DestroyCountingSession` does not clear `$_SESSION`. **Resolved after this report by `d4e68f2` (VF.9/VF.10); see the archive note above.**
3. **TDD table lacks SAFETY NET / REFACTOR columns** and no pre-change safety-net run is documented for the five modified test files (process gap, not a code failure).
4. **Working tree still unclean** — 4 `openspec/changes/scaffold-ci4/*` deletions and untracked `auth-roles/{design,proposal,specs}`, `archive/`, `openspec/specs/app-bootstrap/`. Unchanged by this verify.
5. **`coverage_command` still empty** — `openspec/config.yaml:21` `coverage_command: ""`. Coverage is runnable manually (Xdebug), but CI/process has no configured command.
6. **`UserCreate` coverage 28.57%** — `askUsername/askPassword/askRole` and `readHidden` TTY paths are not exercised (the command test doubles them). Acceptable for IO code, but below 80%.

### SUGGESTION
1. `app/Commands/UserCreate.php:20` `$group = 'auth'` vs `design.md:48` `'snak'`.
2. Apply-progress Test Summary layer counts are off by one: actual Unit 6 / Feature 28 / DB 16 (reported 5/30/15).
3. Spec conflict: staff-auth requires `user:create` to offer `admin`; access-control says only `cashier` is provisioned. Implementation follows staff-auth; confirm intent at archive.
4. staff-auth NFR ("every scenario MUST have a PHPUnit test") is literally unmet for "No credential in repository" (manual `git ls-files` scan only). Treated as WARNING because the scan is definitive and the plan classifies credential checks as manual (1b.4/M4).
5. app-bootstrap scenarios have no automated tests (spec has no PHPUnit NFR for that capability); add migration/rollback tests or record M3 as a permanent operator step.
6. Either drop the post-destroy `with('authError')` or persist it outside the destroyed session (e.g., query flag), and align the test assertion. **Resolved by `d4e68f2` via the notice cookie.**

## 11. Prior findings re-assessment

| Prior finding | Status now |
|---|---|
| CRITICAL-1 mid-session 500 / double destroy | ✅ RESOLVED — live 302, log count 2→2; regression test proven |
| CRITICAL-2 no TDD evidence table | ✅ RESOLVED — table present, rows truthful |
| CRITICAL-3 missing scenario tests | ⚠️ MOSTLY RESOLVED — logout CSRF, dummy verify, cookie flags, filter wiring added; app-bootstrap/manual + repo-scan remain (WARNING) |
| W1 PR size >400 | ❌ STILL OPEN — numbers above |
| W2 test-double fidelity | ✅ RESOLVED for destroy; residual flash-fidelity issue (W2-now) |
| W3 TDD table / safety net | ⚠️ PARTIAL — table exists; safety-net columns absent |
| W4 `.env.example` username drift | ✅ RESOLVED — `database.tests.username = snak` |
| W5 i18n test narrow | ✅ RESOLVED — scans commands/controllers/filters/services/views, no manual list |
| W6 English CLI duplicate message | ✅ RESOLVED — Spanish `Auth.usernameDuplicate`, asserted |
| W7 working tree unclean | ❌ STILL OPEN — same 9 entries |
| W8 coverage skipped | ⚠️ PARTIAL — config still empty; manual coverage now available |
| S5 extend language test | ✅ RESOLVED |

## 12. Final verdict

**PASS WITH WARNINGS** — the functional CRITICAL (mid-session deactivation 500) is fixed and independently reproduced as fixed (302 `/login`, no new destroy errors), the TDD evidence table exists and is truthful, and the new tests are meaningful (including a proven regression-catching double). Remaining items are process/review-size/coverage/UX warnings, not spec failures.
