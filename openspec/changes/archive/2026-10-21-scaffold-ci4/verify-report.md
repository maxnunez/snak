# Verification Report — scaffold-ci4 (app-bootstrap)

**Change**: scaffold-ci4
**Version**: N/A (delta spec, no version field)
**Mode**: Strict TDD (PHPUnit 10.5.66, `vendor/bin/phpunit`, config `phpunit.dist.xml`)
**Verdict**: PASS WITH WARNINGS

> Materialized into the archive from Engram observation #213 (`sdd/scaffold-ci4/verify-report`).

## Completeness
| Metric | Value |
|---|---|
| Tasks total | 31 |
| Tasks complete | 31 (`grep -c '^- \[x\]'` → 31) |
| Tasks incomplete | 0 |

## Executed Evidence
- `vendor/bin/phpunit --testdox` → exit 0, `OK (7 tests, 17 assertions)`, PHP 8.4.26, no app deprecations.
- `composer cs` → exit 0, `Found 0 of 55 files that can be fixed` (PHP-CS-Fixer 3.95.27, @PSR12).
- `curl -H 'Host: snak.local' http://127.0.0.1/health` → 200, body `{"status":"ok","db":"ok"}`, `Cache-Control: no-store, max-age=0, no-cache`.
- `curl -H 'Host: snak.local' http://127.0.0.1/` → 200 (30,445 bytes) from `public/index.php`.
- `php -S 127.0.0.1:8099 -t public public/index.php` → `/` 200, `/health` 200 (proves the `public/` front controller independent of Apache).
- Leak gate: `http://localhost/snak/{.env,composer.json,app/Config/App.php,writable/logs/,vendor/autoload.php}` → all 403; `snak.local` vhost variants → 404.
- `composer migrate` → exit 0; `migrate:status` → "No migrations were found", exit 0; `SHOW TABLES` → `migrations` only; `composer migrate:rollback` → exit 0; `SHOW TABLES` → `migrations` only; `migrations` rows = 0.
- `apache2ctl configtest` → `Syntax OK` (emits only AH00558 FQDN notice); `apache2ctl -S` lists `snak.local` vhost; `/etc/hosts:8` maps `127.0.0.1 snak.local www.snak.local`.
- `composer validate --no-check-publish` → `./composer.json is valid`.
- Git: exactly 5 commits, conventional types, clean tree, no remote.
- Secrets: `.env` not in `git ls-files` (count 0), `.gitignore:47`; DB password (`IglyIKXYeun4RNQ9qVfi`) absent from all tracked files, from every commit in `git rev-list --all`, and from `openspec/`.
- Timezone: `app/Config/App.php:136` → `public string $appTimezone = 'America/La_Paz';`; `php spark` banner prints `UTC-04:00`; AppConfigTest asserts config + `Time::now()`.

## Spec Compliance Matrix
| Requirement | Scenario | Covering evidence | Result |
|---|---|---|---|
| Composer-Installed CI4 Skeleton | Boot from public/ | Apache vhost DocumentRoot `public/`, GET / 200; app//writable//composer.json not reachable (403/404); `composer cs` 0/55 | ✅ COMPLIANT |
| Environment and Secret Isolation | Fresh clone boots | `.env.example` tracked; `diff` of `.env` vs `.env.example` shows only comment lines differ (all keys equal, password empty in template); /health 200 reaches `snak`; timezone wired | ✅ COMPLIANT (manual; not automatable in-suite) |
| Environment and Secret Isolation | No secrets tracked | `git ls-files` no `.env`; password grep over worktree, index and all commits → empty | ✅ COMPLIANT |
| Health Endpoint | DB reachable | `HealthTest::testHealthReturnsOkWhenDatabaseIsReachable` + live 200 ok/ok | ✅ COMPLIANT |
| Health Endpoint | DB unreachable | Manual only: temporary `.env` host 127.0.0.1 port 3307 → 503 `{"status":"error","db":"error"}`; body has no DSN/user/password/port/trace; catch block un-covered by tests | ⚠️ PARTIAL |
| Migration Infrastructure | Fresh migrate | `composer migrate` exit 0 → only `migrations`; rollback exit 0 → no app table, rows 0 | ✅ COMPLIANT (manual CLI, by design D7) |
| Test Runner | Suite passes | `vendor/bin/phpunit` exit 0, 7 tests / 17 assertions | ✅ COMPLIANT |
| Test Runner | Smoke test catches broken boot | All feature tests boot the real app; a boot failure fails them by construction; no dedicated negative test | ✅ COMPLIANT (by construction) |
| Test Runner | Command recorded | `openspec/config.yaml:17` `runner_available: true`, `:19` and `:28` `test_command: "vendor/bin/phpunit"` | ✅ COMPLIANT |
| Base UI Shell | Shell renders | ShellTest 200 + local assets; 5 assets HTTP 200; no CDN `href`/`src` | ✅ COMPLIANT |
| Base UI Shell | Theme applied | `public/assets/css/app.css:2-5` white bg/black text; `:16-38` `#003366` primary/hover/focus/active; `partials/head.php:3` theme-color | ✅ COMPLIANT |
| Base UI Shell | Libraries wired, unused | `partials/scripts.php:1-5` loads jQuery→Bootstrap→SweetAlert2→DataTables; `grep Swal|DataTable` in app/Views+Controllers → none | ✅ COMPLIANT |
| Language Rules | No hardcoded copy | `home/index.php:6-7`, `base.php`, `head.php:4` use `lang()`; no Spanish literals in Views/Controllers/Config | ✅ COMPLIANT |
| Language Rules | Health is English | HealthTest asserts keys `status`,`db`; live values `ok`/`error`; controller comments English | ✅ COMPLIANT |
| PHP 8.4 Compatibility | Clean run | PHP 8.4.26; `composer.json:13 "php": ">=8.4"`; suite green under `failOnWarning`/`failOnRisky`; no app deprecation | ✅ COMPLIANT |

**Compliance summary**: 8/8 requirements PASS; 14/15 scenarios COMPLIANT; 1 PARTIAL (no automated 503 test).

## Coherence (Design)
| Decision | Followed? | Notes |
|---|---|---|
| D1 CI4 4.7.5, root `public/`, lock committed | ✅ | `composer show` → v4.7.5; 104-file scaffold commit |
| D2 `/health` Throwable + `SELECT 1`, 200/503, no-store | ✅ | `app/Controllers/Health.php:21-33`; route `Routes.php:7` |
| D3 PSR-12 gate, Views excluded | ✅ | `.php-cs-fixer.dist.php:4-11`; 0/55 |
| D4 `America/La_Paz` | ✅ | `App.php:136`; spark `UTC-04:00` |
| D4/D9 `app/Config/Snak.php` (BOB) | ⚠️ | Not created; `tasks.md:44` defers it to change 3 — design/tasks conflict |
| D5 vendored assets/versions/theme | ✅ | 9 vendored files + VERSIONS.md; versions match D5 exactly |
| D6 testing (SmokeTest.php) | ⚠️ | Implemented as AppConfigTest/HealthTest/ShellTest; no `SmokeTest.php`; coverage block removed |
| D7 migrations, zero tables | ✅ | Scripts in `composer.json:45-46`; CLI proof |
| D8 layout for later changes | ✅ | `layouts/`, `partials/`, `Language/es/` structure in place |
| D9 5 conventional commits | ✅ | 5 commits, conventional scopes; vhost in commit 1 (tasks 5.4), not commit 5 (design D9) |

## Strict TDD Compliance
| Check | Result | Details |
|---|---|---|
| TDD Evidence reported | ✅ | Table present in apply-progress (#209) |
| Test files exist | ✅ 3/3 | `tests/unit/AppConfigTest.php`, `tests/feature/HealthTest.php`, `tests/feature/ShellTest.php` |
| GREEN confirmed | ✅ 3/3 | All pass on execution (7 tests total) |
| Triangulation adequate | ✅ | 2 cases each for timezone, health, shell |
| Safety net for modified files | ⚠️ | All 3 files are new; "5/5 pre-existing" for ShellTest is not reproducible from history (single scaffold commit) |
| RED independently reproducible | ⚠️ | Tests and code share one commit per unit; RED cannot be replayed from git |
| Assertion quality | ✅ | No tautologies, no ghost loops, no mock-heavy tests |

**TDD Compliance**: 5/7 checks fully confirmed (2 not replayable from history, no violation).

### Test Layer Distribution
| Layer | Tests | Files |
|---|---|---|
| Unit | 2 | 1 (`AppConfigTest`) |
| Integration/Feature (FeatureTestTrait) | 4 | 2 (`HealthTest`, `ShellTest`) |
| Session smoke (appstarter leftover) | 1 | 1 (`tests/session/ExampleSessionTest.php`) |
| **Total** | **7** | **4** |

### Changed-File Coverage (informational)
| File | Line % | Uncovered | Rating |
|---|---|---|---|
| `app/Controllers/Health.php` | 70% | 23, 24, 25 (catch/log block) | ⚠️ Below 80% |
| `app/Controllers/Home.php` | 100% | — | ✅ |
| `app/Controllers/BaseController.php` | 100% | — | ✅ |
| `app/Language/es/App.php` | 100% | — | ✅ |
| `app/Config/Database.php` | 100% | — | ✅ |

Aggregate app lines 25.30% (dominated by untouched `app/Config/*`); no coverage threshold configured.

## Issues Found

**CRITICAL**: None.

**WARNING**
1. **PR 2 exceeds the 400-line review budget — known size:exception gap.** `git diff --shortstat 2f7d856 bb79dee` → `7 files changed, 3555 insertions(+), 881 deletions(-)`; `composer.lock` alone is `3457 873` (`git diff --numstat`). Authored non-lock change is 106 lines. The approved exception was scoped to PR 1, so PR 2 needs an explicit decision; not a functional blocker. (PR 1 actual: 104 files, 9,323 insertions vs ~7,100 forecast, covered by the PR 1 exception.)
2. **No automated regression test for the 503 path.** `app/Controllers/Health.php:23-25` (catch + log) is uncovered (clover: uncovered statements 23, 24, 25; file 70% lines). The scenario is proven only by manual curl. Under a literal reading of the Strict TDD gate this is `UNTESTED`; downgraded to WARNING because the apply task (2.3) specified manual proof and the runtime behavior is verified.
3. **Design/artifact drift after apply.** `design.md` D4/D9 list `app/Config/Snak.php` and D6 lists `tests/feature/SmokeTest.php`; neither exists (tasks.md:44 defers Snak config to change 3; tests were split into three files). The design's Affected Areas also say `phpunit.xml` while the real file is `phpunit.dist.xml`. Design was not refreshed post-implementation.
4. **Development environment leaks Debugbar surface.** `.env.example:5` ships `CI_ENVIRONMENT = development`. The live page injects `src="http://snak.local/?debugbar"` (absolute same-origin URL) and `/?debugbar_time=...` serves a 105 KB toolbar; `/health` also returns `Debugbar-Time`/`Debugbar-Link` headers. No credential appeared in the 105 KB payload (grepped) and the JSON body stays clean, but `ShellTest`'s no-external-origin assertion cannot see this because the test environment disables Debugbar. Production must set `production`; the template should say so explicitly.

**SUGGESTION**
1. Commits 3, 4 and 5 bundle `openspec/.../tasks.md` checkbox edits into code commits (e.g. `bb79dee` touches tasks.md). Keep artifact updates out of reviewable code units.
2. `tests/session/ExampleSessionTest.php` (appstarter example, 1 of 7 tests) remains although D6 said to delete appstarter examples.
3. `composer.json` still identifies as `codeigniter4/appstarter` / "CodeIgniter4 starter app" and keeps unused dev deps `fakerphp/faker` and `mikey179/vfsstream`.
4. `tests/feature/ShellTest.php:25` asserts the meta string `content="#003366"` (implementation detail); lines 22-24 couple to asset filenames by substring. Consider asserting rendered theme/behavior instead.
5. Host PHP CLI emits `PHP Warning: JIT is incompatible with third party extensions that override zend_execute_ex()` when using the built-in server (opcache.jit + Xdebug). Environmental, not app code.
6. `tasks.md` 1.9 says `.env` is `chmod 600`; actual is 640 + group/ACL read for www-data (deliberate, documented in apply-progress). Task text is stale.

## Verification Environment Changes (503 proof, reverted)
- Temporarily edited `.env` lines 8 and 16: `database.default.hostname = 127.0.0.1`, `database.default.port = 3307` (closed port). Observed `HTTP/1.1 503 Service Unavailable`, body `{"status":"error","db":"error"}`; greps for password, user, host, port, `SQLSTATE`, `Exception`, `DatabaseException`, `SELECT` and trace markers found nothing. Ran as a local-only, untracked file change.
- Restored `.env` from `/tmp/snak-env-backup`; MD5 before and after both `d28fd67022e62aa0de258908587738b5`; mode 640 preserved; `/health` returned 200 again.
- Residual side effect: the in-place edit changed the file's owning group label from `www-data` to `max`. The POSIX ACL (`group:www-data:rwx #effective:r--`) and mode 640 are unchanged, so Apache can still read it (verified 200 after restore). The group label cannot be restored without root (`sudo -n` → "a password is required"); content is byte-identical.
- The failed probe wrote one line to `writable/logs` (gitignored): `Health check database failure: Unable to connect to the database.` — no DSN or credentials.
- Database left exactly as found: tables `{migrations}`, `migrations` rows 0 (same as before `composer migrate`).
- `git status --porcelain` empty before and after verification.

## Verdict
**PASS WITH WARNINGS** — all 8 requirements are functionally satisfied and the suite/style/leak/migration gates pass; the open items are the PR 2 lockfile size decision and the missing automated 503 regression test.
