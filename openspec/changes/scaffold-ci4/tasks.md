# Tasks: scaffold-ci4 (app-bootstrap)

CI4 4.7.5 via `create-project` → `/tmp` + `rsync`; `/health`; PSR-12; Bootstrap 4 shell; zero domain tables. 3 stacked PRs / 5 commits (design D9).

## Review Workload Forecast

```text
Decision needed before apply: Resolved (size:exception approved for PR 1, generated code only)
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High
```

| Unit | PR | Deliverable | Changed lines (authored + generated) |
|---|---|---|---|
| 1 | PR 1 | CI4 + env + PHPUnit | ~100 + ~7,100 (`app/Config` ~3,100, lock ~4,000) |
| 2 | PR 2 | `/health` + PSR-12 | ~135 + lock hunk ~420 |
| 3 | PR 3 | shell + migrations + ops | ~280 + vendored ~20 lines / 1.2 MB |

PR 1 needs `size:exception` (generated only): review `composer.json`, `.env.example`, `.gitignore`, `deploy/apache/snak.conf`, `tests/unit/AppConfigTest.php`; skim lockfile.

## Phase 0 — Host prerequisites (manual, sudo, no PR)

No passwordless sudo: run interactively.

- [x] 0.1 `deploy/apache/snak.conf`: `ServerName snak.local`, `DocumentRoot /var/www/html/snak/public`, `AllowOverride None`, `FallbackResource /index.php`.
- [x] 0.2 `sudo ln -s /var/www/html/snak/deploy/apache/snak.conf /etc/apache2/sites-available/snak.conf && sudo a2ensite snak && sudo apache2ctl configtest && sudo systemctl reload apache2`. Prove: `Syntax OK`; `apache2ctl -S`.
- [x] 0.3 Leak gate before any password: `curl -so /dev/null -w '%{http_code}' http://localhost/snak/.env` → 403/404. If 200, deny `/var/www/html/snak` in `000-default.conf` and reload.

## Phase 1 — Unit 1: CI4 import + env baseline (PR 1, commit 1)

- [x] 1.1 Scaffold, no test; verified by boot + smoke: `composer create-project codeigniter4/appstarter:4.7.5 /tmp/snak-scaffold --no-install` then `rsync -a /tmp/snak-scaffold/ /var/www/html/snak/`.
- [x] 1.2 `composer.json`: `"php": ">=8.4"`, `codeigniter4/framework: "~4.7.5"`, `phpunit/phpunit: ^10.5.66`; `composer install` → `composer.lock`.
- [x] 1.3 `.gitignore`: `/vendor/`, `.env`, `writable/*`.
- [x] 1.4 `.env.example` (tracked): `snak`, utf8mb4, MySQLi, localhost, `app.appTimezone = America/La_Paz`, empty password.
- [x] 1.5 Scaffold, no test: delete `tests/unit/HealthTest.php`, `tests/database/ExampleDatabaseTest.php`, `tests/_support/{Models,Database}`; add `phpunit.dist.xml` (CI4 bootstrap, `failOnWarning`). Proved by 1.7.
- [x] 1.6 RED: `tests/unit/AppConfigTest.php` asserts `config('App')->appTimezone === 'America/La_Paz'`. Prove: `vendor/bin/phpunit` fails (appstarter is UTC).
- [x] 1.7 GREEN: `app/Config/App.php` → `$appTimezone = 'America/La_Paz'`. Prove: `vendor/bin/phpunit` exits 0.
- [x] 1.8 Same unit as 1.2: `openspec/config.yaml` → `runner_available: true`, `testing.test_command` and `verify.test_command: "vendor/bin/phpunit"`. Prove: grep.
- [x] 1.9 `.env` from `.env.example` + real DB password, `chmod 600`, only after 0.3. Prove: `git ls-files | grep -c '^\.env$'` = 0.
- [x] 1.10 `git init`; commit 1 `chore(scaffold): import CI4 4.7.5 appstarter and env baseline` (+ `deploy/apache/snak.conf`). Prove: `composer validate`, `php spark serve` → `GET /` 200, clean status.
- [x] 1.11 `sudo chgrp -R www-data writable && sudo chmod -R 775 writable`.

Later, not here: BOB in `app/Config/Snak.php` (`currencyCode`, `currencySymbol`, `currencyDecimals`, `lowStockThreshold`) + `app/Helpers/money_helper.php` (change 3). Only `America/La_Paz` ships now.

## Phase 2 — Unit 2: DB-aware `/health` (PR 2, commit 2)

- [ ] 2.1 RED: `tests/feature/HealthTest.php` — GET `/health` 200, `{"status":"ok","db":"ok"}`, `Cache-Control: no-store`. Prove: phpunit fails.
- [ ] 2.2 GREEN: `app/Controllers/Health.php` (`Database::connect()->query('SELECT 1')` in `try/catch (\Throwable)`, log only) + `app/Config/Routes.php`: `$routes->get('health', 'Health::index')`. Prove: phpunit green.
- [ ] 2.3 Negative: break `database.default.hostname` in `.env`; GET `/health` → 503 `{"status":"error","db":"error"}`, no DSN/user/password/trace; restore.
- [ ] 2.4 Commit 2 `feat(health): add DB-aware health endpoint`.

## Phase 3 — Unit 3: PSR-12 gate (PR 2, commit 3)

- [ ] 3.1 Tooling, no unit test: `composer require --dev friendsofphp/php-cs-fixer:^3.95`; `.php-cs-fixer.dist.php` (`@PSR12`, `app/` + `tests/`, `exclude('app/Views')`); `cs`/`cs:fix` scripts.
- [ ] 3.2 `composer cs:fix` then `composer cs` exits 0. Prove: exit code.
- [ ] 3.3 Commit 3 `chore(style): enforce PSR-12 with PHP-CS-Fixer`.

## Phase 4 — Unit 4: base shell, local assets (PR 3, commit 4)

- [ ] 4.1 RED: `tests/feature/ShellTest.php` — GET `/` 200, no external origin, links `/assets/css/app.css`, SweetAlert2, DataTables, `#003366` present. Prove: phpunit fails.
- [ ] 4.2 Scaffold, no test (vendored binaries): `public/assets/vendor/{bootstrap/4.6.2,jquery/3.7.1,sweetalert2/11.26.25,datatables/1.13.11}/*` + `public/assets/VERSIONS.md`. Proved by 4.1.
- [ ] 4.3 `public/assets/css/app.css`: `#003366` primary (hover/focus/active), `.bg-primary`, links, black on white. Proved by 4.1.
- [ ] 4.4 `app/Views/layouts/base.php`, `app/Views/partials/{head,scripts}.php`, `app/Views/home/index.php`, `app/Language/es/App.php`, `app/Controllers/Home.php`, `$routes->get('/', 'Home::index')`; no prose outside Language. Prove: phpunit green.
- [ ] 4.5 Commit 4 `feat(ui): add base layout with vendored Bootstrap 4 assets`.

## Phase 5 — Unit 5: migrations + ops (PR 3, commit 5)

- [ ] 5.1 `app/Database/{Migrations,Seeds}/.gitkeep`, default `app/Config/Migrations.php`; `migrate`/`migrate:rollback` scripts in `composer.json`.
- [ ] 5.2 Manual before migrate: `SHOW GRANTS FOR 'snak'@'localhost'` must include `CREATE` (+ `ALTER`/`DROP`/`INDEX`) on `snak.*`; else STOP and report (root grant needed).
- [ ] 5.3 Gate: `php spark migrate --all` exit 0; `SHOW TABLES` ⊆ `{migrations}`; `migrate:status`, `migrate:rollback --all` exit 0; no app table left.
- [ ] 5.4 Commit 5 `chore(ops): add migration scripts` (vhost in commit 1 so PR 1 can hold credentials).
- [ ] 5.5 PR 3 verify: `vendor/bin/phpunit`, `composer cs`, `apache2ctl configtest`, `curl -H 'Host: snak.local' http://127.0.0.1/health` → 200 ok/ok, `curl http://localhost/snak/.env` → 403/404.
