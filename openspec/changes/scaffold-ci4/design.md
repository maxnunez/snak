# Design: scaffold-ci4 (app-bootstrap)

## Technical Approach

CI4 4.7 appstarter imported into the non-empty root, plus what it lacks: env wiring, DB-aware `/health`, PSR-12 + PHPUnit gates, vendored assets, base layout. No domain tables. Proven by `php spark serve` and `vendor/bin/phpunit`; Apache is an optional operator step.

**Verified**: PHP 8.4.26 (intl/dom/mysqli/gd), Composer 2.10, CI4 4.7.5 current. `AllowOverride None` under `/var/www/` (`.htaccess` inert, mod_rewrite off). No passwordless sudo.

## Architecture Decisions

**D1 — Version, install, app root.** Root = `/var/www/html/snak`; `public/` is the web root. `composer create-project codeigniter4/appstarter:4.7.5 /tmp/snak --no-install` (target must be empty; ours is not), `rsync` over `openspec/` and `.atl/`, set `php >=8.4` + `codeigniter4/framework: "~4.7.5"` (patches only = pin a minor), `composer install`, commit `composer.lock`. Rejected: framework-only skeleton (~40 Config files); subdir install (breaks `APPPATH`).

`deploy/apache/snak.conf`: `ServerName snak.local`, `DocumentRoot /var/www/html/snak/public`, `AllowOverride None`, `FallbackResource /index.php` (no mod_rewrite). Enable once: `a2ensite snak`, reload, `chgrp -R www-data writable && chmod -R 775 writable`. Symlinks cannot hide `app/`, `vendor/`, `.env` from the default `/var/www/html` vhost. **No real credentials before the vhost is live** — `http://localhost/snak/.env` is fetchable today.

**D2 — `/health`.** `App\Controllers\Health::index()`, `$routes->get('health', 'Health::index')`:

```php
try { \Config\Database::connect()->query('SELECT 1'); }
catch (\Throwable $e) { /* log only */ }
```

`BaseConnection::initialize()` throws `DatabaseException` even with `DBDebug=false`, so `Throwable` is mandatory; catching it avoids CI4's 500 page and trace, and `SELECT 1` also catches cached-but-dead connections. 200 `{"status":"ok","db":"ok"}` / 503 `{"status":"error","db":"error"}`, `Cache-Control: no-store`; detail only in `writable/logs` — no DSN, user, password, or message in the body.

**D3 — PSR-12 gate.** `.php-cs-fixer.dist.php`: `@PSR12`, finder `app/` + `tests/`, `exclude('app/Views')` (templates). Dev pin `friendsofphp/php-cs-fixer: ^3.95`. `cs` = `@php vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no`; `cs:fix` = without dry-run. Closes the spec's open PSR-12 risk.

**D4 — Timezone, currency.** `Config\App::$appTimezone = 'America/La_Paz'`; CI4 calls `date_default_timezone_set()` at boot (4.7.5:191), so `Time::now()`, migration timestamps and report boundaries are UTC-4, no DST. `.env` repeats `app.appTimezone` (env wins; both must match — the duplicate is the clone proof). Currency unused now: create `app/Config/Snak.php` (`currencyCode='BOB'`, `currencySymbol='Bs'`, `currencyDecimals=2`, integer minor units). Change 3 adds `lowStockThreshold` there and formats via `app/Helpers/money_helper.php`; no relocation.

**D5 — Assets: vendored, not npm.** `public/assets/vendor/{lib}/{version}/`:

| Library | Version | Files |
|---|---|---|
| Bootstrap | 4.6.2 (final 4.x) | `bootstrap.min.css`, `bootstrap.bundle.min.js` |
| jQuery | 3.7.1 | `jquery.min.js` |
| SweetAlert2 | 11.26.25 | `sweetalert2.min.css`, `sweetalert2.all.min.js` |
| DataTables + BS4 | 1.13.11 | `jquery.dataTables.min.{js,css}`, `dataTables.bootstrap4.min.{js,css}` |

Server-rendered: no bundler/Sass; deployment stays `git clone` + `composer install`. npm (present) would add `node_modules`, a lockfile, and a copy step for six files. jQuery 3.7.1 because BS4 breaks on jQuery 4; DataTables 1.13.11 is the last 1.x with documented BS4 integration. `VERSIONS.md` records provenance. Theme `public/assets/css/app.css` (`.btn-primary` incl. `:hover`/`:focus`/`:active`, `.bg-primary`, `.text-primary`, links, black-on-white). Layout `layouts/base.php` → `partials/head.php`, `renderSection('content')`, `partials/scripts.php` (jQuery → Bootstrap → SweetAlert2 → DataTables), `renderSection('pageScripts')`. Loaded, never invoked; no `app.js` until change 2; `es-ES.json` in change 3.

**D6 — Testing.** `phpunit.dist.xml` at root (auto-loaded; no duplicate `phpunit.xml`). Pin `phpunit/phpunit: ^10.5.66` to match its schema — PHPUnit 11's deprecated-schema warning turns red under `failOnWarning="true"`. Delete appstarter examples (`tests/database/ExampleDatabaseTest.php`, `tests/unit/HealthTest.php`, `_support/{Models,Database}`): they need a nonexistent `ci4_test` group. Add `tests/feature/SmokeTest.php`: `/health` 200 + JSON, `/` 200 + local assets + `#003366`, timezone config. **No separate test DB**: zero tables, zero writes, so a `tests` group adds provisioning, duplicated credentials, and a failure mode with no isolation benefit; change 2 adds writes and must add a `tests` group or transaction wrapping. Config: `testing.runner_available: true`; `testing.test_command` and `verify.test_command` = `"vendor/bin/phpunit"`.

**D7 — Migrations, zero tables.** `app/Database/{Migrations,Seeds}/` empty with `.gitkeep`; `Config/Migrations.php` default. Scripts `migrate` = `php spark migrate --all`, `migrate:rollback` = `php spark migrate:rollback --all`. CLI proof (not PHPUnit — mutates the real DB): `migrate --all` exits 0, `SHOW TABLES` ⊆ `{migrations}` (CI4 creates it only when a migration runs), `migrate:status` and rollback exit 0, no app table remains.

**D8 — Layout for changes 2–8.** `app/Controllers/*.php` (2), `app/Controllers/Api/*.php` + route group `api` (3), `app/Services/*Service.php` (5–7), `app/Models/*Model.php` (2,3,5), `app/Filters/*Filter.php` (2,4,6), `app/Views/{feature}/` + shared `layouts/`, `partials/` (2+), `app/Language/es/{Feature}.php` with `$language='es'` (2+), `writable/uploads/products/` (3). Single `App\` namespace, no CI4 modules.

**D9 — Commits, budget.** 1 `chore(scaffold): import CI4 4.7.5 appstarter and env baseline`; 2 `feat(health): add DB-aware health endpoint` (+`Config/Snak.php`); 3 `chore(style): enforce PSR-12 with PHP-CS-Fixer`; 4 `feat(ui): add base layout with vendored Bootstrap 4 assets` (+`Home`, `es/App.php`, shell tests); 5 `chore(ops): add migration scripts and Apache vhost template`. PR 1 = commit 1, PR 2 = 2–3, PR 3 = 4–5, stacked-to-main, tests with code.

**Cannot fit 400 lines**: commit 1 is ~3,100 generated lines (`app/Config`); authored work is ~400 (PR 2 ≈ 130, PR 3 ≈ 250). Split at the scaffold boundary; PR 1 needs `size:exception` scoped to generated code — reviewer reads `composer.json`, `.env.example`, `.gitignore`, deleted tests only.

## Data Flow

```
GET /health → Health::index() ├─ SELECT 1 ok → 200 ok/ok
                              └─ Throwable  → 503 error/error
GET / → Home::index() → home/index extends layouts/base (head · content · scripts)
```

## File Changes

| File | Action | Description |
|---|---|---|
| `composer.json` / `composer.lock` | Create | PHP `>=8.4`, framework `~4.7.5`, dev tools, scripts |
| `.gitignore`, `.env.example` | Create/modify | ignore rules; DB/timezone template |
| `app/Config/{App,Snak,Routes}.php` | Create/modify | timezone, language, BOB, routes |
| `app/Controllers/{Health,Home}.php` | Create/modify | probe; shell |
| `app/Views/layouts/base.php`, `partials/*`, `home/index.php` | Create | shell |
| `app/Language/es/App.php` | Create | shell copy |
| `public/assets/{vendor,css}`, `VERSIONS.md` | Create | assets, theme |
| `tests/feature/SmokeTest.php` | Create | three assertions |
| `tests/{unit/HealthTest.php,database/ExampleDatabaseTest.php}` | Delete | need absent test DB |
| `phpunit.dist.xml`, `.php-cs-fixer.dist.php` | Create/modify | runner, style |
| `app/Database/{Migrations,Seeds}/.gitkeep` | Create | migration infra |
| `deploy/apache/snak.conf` | Create | DocumentRoot `public/` |
| `openspec/config.yaml` | Modify | runner + `verify.test_command` |

## Interfaces / Contracts

`GET /health` → `200|503`, JSON keys exactly `status`, `db`, values `ok|error`. `layouts/base.php` exposes required `content` and optional `pageScripts` sections.

## Testing Strategy

Feature (`FeatureTestTrait`, live `snak`, read-only): `/health` status/shape; `/` theme and local-only assets. Unit: `appTimezone` config. CLI: `spark serve`, `migrate --all`, `composer cs`.

## Migration / Rollout

None. Rollback: revert scaffold commit, delete `vendor/ app/ public/ writable/`; no tables to undo.

## Open Questions

- [ ] Does `snak`@localhost hold `CREATE` on `snak.*`? `/health` passes without it, `migrate --all` fails — check `SHOW GRANTS`.
- [ ] Vhost needs one interactive sudo step; else `spark serve` stays the verify path and `.env` holds no real password.
