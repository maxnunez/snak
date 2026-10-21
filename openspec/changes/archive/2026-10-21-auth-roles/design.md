# Design: Staff Authentication and Authorization (`auth-roles`)

## Technical Approach

Two migrations, one model, two small services, one filter, one controller pair. No third-party auth. Every rule from `staff-auth` and `access-control` is enforced at exactly one place: schema/ENUM + service validation for accounts, `AuthService` for credentials, `StaffFilter` for the gate, `Config\Filters`/`Config\Routes` for wiring. Verified against CI4 4.7.5 source (not assumed): `Session::destroy()` is a **no-op when `ENVIRONMENT === 'testing'`**, `FeatureTestTrait` **wipes `$_SESSION` between calls**, `Security::verify()` **throws** (not redirects) when `$redirect` is false, and `PASSWORD_DEFAULT` on PHP 8.4 is bcrypt cost **12**.

**Spec mapping**: the column is `is_active` (locked by the orchestrator); it is the `active` attribute named in `staff-auth`. No behavioural delta.

## Architecture Decisions

### D1 — Schema

**Choice**: two tables, two fixed-timestamp migrations.

```php
// 2026-10-21-100000_CreateUsers.php
'id'            => ['type' => 'INT', 'constraint' => 9, 'unsigned' => true, 'auto_increment' => true],
'username'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
'role'          => ['type' => 'ENUM', 'constraint' => ['cashier', 'admin'], 'default' => 'cashier', 'null' => false],
'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false],
'last_login_at' => ['type' => 'DATETIME', 'null' => true],
'created_at'    => ['type' => 'DATETIME', 'null' => true],
'updated_at'    => ['type' => 'DATETIME', 'null' => true],
// addKey('id', true); addUniqueKey('username')

// 2026-10-21-100100_CreateLoginAttempts.php
'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
'username'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
'attempted_at' => ['type' => 'DATETIME', 'null' => false],
// addKey(['username', 'ip_address', 'attempted_at'])  → 47 chars, under MySQL's 64
// addKey('attempted_at')                              → purge range
```

**down()**: `CreateLoginAttempts` → `dropTable('login_attempts', true)`; `CreateUsers` → `dropTable('users', true)`. Both migrations are created in the same batch, so one `migrate:rollback` drops both.

**Rejected**: `VARCHAR(20)` for `role` (validation-only enforcement — Forge emits `ENUM('cashier','admin')` correctly from an array constraint, so the DB rejects bad roles too, per `access-control`); FK from `login_attempts.username` to `users.username` (unknown usernames must be recordable, and an FK would leak/deny enumeration symmetry); `SET` (multiple roles are out of scope).

**Cleanup**: `LoginThrottleService::purge()` deletes `attempted_at < now-24h`, called at the top of `recordFailure()`. It can never touch the 15-min window (24h ≫ 15min). No cron required; old rows are outside every window query anyway. Retention bounds "auditable" to 24h — deliberate.

**`UserModel`**: `$table='users'`, `$primaryKey='id'`, `$returnType='object'`, `$useTimestamps=true`, `$allowedFields=['username','password_hash','last_login_at']`. `role` and `is_active` are intentionally **not** allowed fields; they are written only by explicit methods (`createStaff()`, `setActive()`) so no request array can mass-assign them. MySQLi returns numeric columns as strings (`numberNative=false`), so `is_active` is compared as `(int) $row->is_active === 1`.

**Seed command** — `app/Commands/UserCreate.php`, auto-discovered from `app/Commands/` (`Commands::discoverCommands()` uses `listFiles('Commands/')`):

```php
protected $name = 'user:create';
protected $group = 'snak';
protected $usage = 'user:create';
protected $arguments = [];
protected $options = [];

public function run(array $params): int;            // EXIT_SUCCESS | EXIT_ERROR
protected function askUsername(): string;           // CLI::prompt, trim + strtolower
protected function askPassword(): string;           // readHidden() twice, compare
protected function askRole(): string;               // CLI::prompt('Rol', ['cashier','admin'])
```

`askPassword()` uses `stty -echo` around `fgets(STDIN)` inside `try/finally` and refuses to run when `! stream_isatty(STDIN)` (no echo, no log, no shell-history argument, no default credential). It calls `service('auth')->createUser(...)`; on `ValidationException` it prints each message via `$this->error()` and returns `EXIT_ERROR` **before** any insert, satisfying "duplicate refused, writes nothing". A lost race on the unique index surfaces as `DatabaseException` → same non-zero path. Success output is `User #<id> created with role <role>.` — never the password or hash. The three `ask*` methods are `protected` so tests can subclass and bypass stdin (intentional seam).

**Rejected**: `--password` option (shell history); prompting with `CLI::prompt()` (echoes the password); a seeder with a default credential (forbidden).

### D2 — Services

```php
final class AuthService
{
    public const SESSION_USER_ID  = 'user_id';
    public const SESSION_INTENDED = 'intended_url';

    public function __construct(private UserModel $users, private LoginThrottleService $throttle) {}

    public function attempt(string $username, string $password, string $ip): bool;
    public function logout(): void;
    public function currentUser(): ?object;      // no memoisation — see rationale
    public function isAuthenticated(): bool;
    public function createUser(string $username, string $password, string $role = 'cashier', bool $active = true): int;
    private static function dummyHash(): string;
}
```

`attempt()` order: normalize (`strtolower(trim())`) → blocked? (`dummyVerify`, return false) → lookup → `password_verify` against `$user->password_hash ?? dummyHash()` → if unknown/wrong/inactive: `recordFailure`, return false → success: `resetWindow`, `session()->regenerate(true)`, **then** `set('user_id', (int) $user->id)`, `update($id, ['last_login_at' => Time::now()->toDateTimeString()])`, return true.

- **Regeneration** lives in `AuthService`, not the controller, so no future entry point can forget it. Order matters: regenerate first, write data after.
- **Session payload is `user_id` only** — no role, no username. The gate re-reads the row every request, which is what makes role/active changes take effect immediately (`access-control`).
- **Intended URL restore** lives in `AuthController` (private `pullIntendedUrl(): string`): read `intended_url`, accept only values starting with `/` and not `//`, then remove it; default `'/cashier'`. The filter stores it.
- **Inactive mid-session** is handled in the filter (D3), not in `attempt()`.
- **No memoisation of `currentUser()`**: three feature tests in one PHP process share the `auth` service, so a cached user would survive logout/deactivation between simulated requests. Correctness over one indexed PK lookup.
- `logout()` does `remove([SESSION_USER_ID, SESSION_INTENDED])` **and** `destroy()`. The explicit `remove()` is mandatory: `Session::destroy()` is a no-op under `ENVIRONMENT === 'testing'`, so without it the test could not prove the session is gone.

`createUser()` runs CI4 `Validation` (never the model's `$allowedFields` path) with `username: required|min_length[3]|max_length[50]|regex_match[/^[a-z0-9._-]+$/]|is_unique[users.username]`, `role: required|in_list[cashier,admin]`, then the byte-length rule, then `UserModel::createStaff()`. Failures throw `App\Exceptions\ValidationException` carrying `getErrors(): array` keyed by field — one error bag for the command, the tests, and any future admin form.

### D3 — Filters and routes

```php
// Config\Filters
public array $aliases = [ /* … */ 'staff' => \App\Filters\StaffFilter::class ];
public array $globals = ['before' => ['csrf'], 'after' => []];   // was: all commented
```

`StaffFilter::before()` — anonymous **redirect**, wrong role **403**; never the other way round:

```php
$session = service('session');
$userId  = $session->get(AuthService::SESSION_USER_ID);
if ($userId === null) {
    if ($request->getMethod() === 'GET' && $request->getUri()->getPath() !== '/login') {
        $session->set(AuthService::SESSION_INTENDED, $request->getUri()->getPath()
            . ($request->getUri()->getQuery() !== '' ? '?' . $request->getUri()->getQuery() : ''));
    }
    return redirect()->to('/login');                    // 302, no protected markup
}
$user = service('auth')->currentUser();
if ($user === null || (int) $user->is_active !== 1) {
    $session->remove([AuthService::SESSION_USER_ID, AuthService::SESSION_INTENDED]);
    $session->destroy();
    return redirect()->to('/login')->with('authError', lang('Auth.sessionExpired'));
}
$allowed = $arguments ?? [];
if ($allowed !== [] && ! in_array($user->role, $allowed, true)) {
    return service('response')->setStatusCode(403)->setBody(view('errors/403'));
}
return null;
```

Unknown arguments fail closed (no role matches → 403). The 403 path renders `app/Views/errors/403.php` (message + link to `/`), so no redirect loop and no role detail. The filter never trusts a role from the session.

```php
// Config\Routes
$routes->get('login',  'AuthController::showLogin');
$routes->post('login', 'AuthController::login');
$routes->post('logout', 'AuthController::logout');
$routes->group('cashier', ['filter' => 'staff:cashier'], static function (RouteCollection $routes): void {
    $routes->get('/', 'CashierController::index');
});
```

`/`, `/health` stay untouched and public. `/login` and `/logout` are outside the group, so they are public but still CSRF-guarded by the global filter. No controller contains a role or `is_active` check.

### D4 — Session, cookies, CSRF

| Setting | Value | Where |
|---|---|---|
| `session.driver` | `FileHandler`, `savePath = WRITEPATH.'session'` | `Config/Session.php` (unchanged) |
| `session.expiration` | 7200 | unchanged |
| `session.regenerateDestroy` | **true** (was false) | `Config/Session.php` (1 line) |
| `cookie.httponly` / `samesite` | `true` / `'Lax'` | already correct — no change |
| `cookie.secure` | `false` in code; `cookie.secure = true` in the production `.env` | env override, no `ENVIRONMENT` branch |
| CSRF field/cookie/header | `csrf_test_name` / `csrf_cookie_name` / `X-CSRF-TOKEN` | unchanged defaults |
| CSRF regeneration | `Security::$regenerate = true` (framework does it on every verified POST) | unchanged |
| Session ID regeneration | explicit `regenerate(true)` in `AuthService::attempt()` | code |

`cookie.secure` is **not** toggled in PHP: CI4 applies `cookie.secure` from `.env` to `Config\Cookie`, so development (`spark serve` over HTTP) keeps `false` and production sets `true`. `.env.example` documents both lines with a comment; the value is a deployment fact, not a code branch. `Security::$redirect` stays `(ENVIRONMENT === 'production')`: a bad token throws a 403 `SecurityException` in dev/testing and redirects back with flashdata in production. Do **not** call `generateHash()` on login — `verify()` already rotates the token and `csrf_field()` on the next render picks up the new hash; a manual call would rotate it twice.

### D5 — Throttling

```php
final class LoginThrottleService
{
    public const WINDOW_MINUTES  = 15;
    public const MAX_FAILURES    = 5;
    public const RETENTION_HOURS = 24;

    public function __construct(private BaseConnection $db) {}

    public function isBlocked(string $username, string $ip): bool;    // failures() >= 5
    public function failures(string $username, string $ip): int;
    public function recordFailure(string $username, string $ip): void; // purge() then INSERT
    public function resetWindow(string $username, string $ip): void;
    public function purge(): int;
}
```

```php
public function failures(string $username, string $ip): int
{
    $since = Time::now()->subMinutes(self::WINDOW_MINUTES)->toDateTimeString();

    return $this->db->table('login_attempts')
        ->where('username', $username)->where('ip_address', $ip)
        ->where('attempted_at >=', $since)
        ->countAllResults();
}
```

`>=` is intentional: a row exactly 15 minutes old still counts. With 5 recorded failures the 6th attempt is blocked **even with the correct password** (block check runs before the lookup). `resetWindow()` deletes only rows `WHERE username=? AND ip_address=? AND attempted_at >= since` — the counter resets, older rows stay for the 24h audit horizon. Key is `(normalized username, $request->getIPAddress())`; `attempted_at` is always written with `Time::now()` (app timezone `America/La_Paz`), never `date()`, so string comparison stays consistent.

**Testability without sleeps**: services use `Time::now()`; tests call `Time::setTestNow('2026-10-21 10:00:00')` and insert historical rows directly with explicit `attempted_at`. "5 failures older than 15 min" = insert at `09:44:59`; "6th blocked" = insert 5 at `09:55:00` then attempt at `10:00:00`.

### D6 — Password policy

Policy is enforced in `AuthService::createUser()` (**service**, not controller): the CLI command is a second writer and must not carry its own copy. `strlen($password) >= 8 && strlen($password) <= 72` — bytes, not characters, because that is bcrypt's limit; copy says "8 caracteres … 72 bytes".

Hashing is `password_hash($password, PASSWORD_DEFAULT)` (bcrypt cost 12 on PHP 8.4) and `password_verify`. Login does **not** validate password length: a short password is just a failed login with the generic message, so the policy is not disclosed.

Generic failure: every branch (unknown user, wrong password, inactive, throttled) returns `false` and the controller flashes `lang('Auth.loginFailed')`. Unknown users verify against `self::dummyHash()` — a lazily built `password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)` held in a `static ?string`. **Rejected**: a committed dummy hash constant (no secret-looking blob in the repo, and no cost drift when PHP changes `PASSWORD_DEFAULT`); hashing the submitted password instead of verifying (different timing profile).

### D7 — i18n

`app/Language/es/Auth.php` (already `defaultLocale = 'es'` in `Config/App.php` — verified, no change needed):

```php
return [
    'loginTitle'        => 'Ingreso del personal',
    'loginHeading'      => 'Acceso al sistema',
    'usernameLabel'     => 'Usuario',
    'passwordLabel'     => 'Contraseña',
    'loginButton'       => 'Ingresar',
    'logoutButton'      => 'Salir',
    'loginFailed'       => 'Usuario o contraseña incorrectos.',
    'sessionExpired'    => 'Su sesión terminó. Ingrese nuevamente.',
    'forbidden'         => 'No tiene permiso para acceder a esta sección.',
    'usernameRequired'  => 'Ingrese su usuario.',
    'passwordRequired'  => 'Ingrese su contraseña.',
    'passwordLength'    => 'La contraseña debe tener al menos 8 caracteres y no superar los 72 bytes.',
    'cashierHome'       => 'Área de caja',
    'signedInAs'        => 'Sesión iniciada como',
];
```

Views and the controller may only use `lang('Auth.*')`; tests never hardcode Spanish. `AuthLanguageTest` scans `app/Views/auth/`, `app/Views/cashier/`, `app/Views/errors/403.php` and `AuthController.php` with `/lang\('Auth\.([A-Za-z0-9_]+)'\)/` and asserts each captured key exists and is a non-empty string — so a key added to a view without the file fails the suite.

### D8 — Tests and the `tests` database

| File | Layer | Covers |
|---|---|---|
| `tests/database/UserModelTest.php` | DB | unique username (index too), `ENUM` rejects bad role, `in_list` rejects it first, hash round-trip, `last_login_at` null, `password_hash` absent from rendered output |
| `tests/database/UserCreateCommandTest.php` | DB + CLI | subclassed `ask*` seam: creates active cashier, duplicate → non-zero + no row, 7-byte password → Spanish message, captured output contains neither password nor hash |
| `tests/database/LoginThrottleServiceTest.php` | DB | window count, boundary at exactly 15 min, purge at 24h, `resetWindow` leaves older rows |
| `tests/feature/AuthTest.php` | Feature + DB | login success (`didRegenerate`, `last_login_at`, redirect), intended URL, wrong password, unknown user (same status + message), inactive at login, logout, CSRF missing/invalid token, 6th attempt blocked, expired window |
| `tests/feature/StaffFilterTest.php` | Feature + DB | anonymous → 302 + no cashier markup, cashier → 200, `staff:admin` → 403, deactivated mid-session → 302 + session gone, `/`, `/login`, `/health` public, role change effective next request |
| `tests/unit/AuthLanguageTest.php` | Unit | key presence (D7) |

**Wiring** (all in slice 1a, because `UserModelTest` is the first DB test):

- `Config/Database.php` `$tests`: MySQLi, `hostname 127.0.0.1`, `database 'snak_test'`, empty `username`/`password`, **`DBPrefix = ''`** (the shipped `db_` prefix would create `db_users` and desync schema from production), `charset utf8mb4`, `DBCollat utf8mb4_unicode_ci`, `numberNative false`. `Database::__construct()` already forces `defaultGroup = 'tests'` when `ENVIRONMENT === 'testing'`, so models hit `snak_test` too.
- `tests/bootstrap.php` (new, `phpunit.dist.xml` → `bootstrap="tests/bootstrap.php"`): `require_once vendor/.../Config/DotEnv.php`, `(new DotEnv(__DIR__.'/..', '.env.testing'))->load()`, then `require vendor/.../Test/bootstrap.php`. `.env.testing` is gitignored and wins over `.env` because `DotEnv::setVariable()` never overwrites an already-set non-empty value while `.env` loads second.
- `.env.example` documents `database.tests.*` (username `snak_test`, blank password) and `cookie.secure`; `.gitignore` gains `.env.testing`.
- Tests use `DatabaseTestTrait` with `$DBGroup='tests'`, `$migrate=true`, `$migrateOnce=true`, `$refresh=false`, **`$namespace=null`** (default `Tests\Support` would look under `tests/_support/Database/Migrations`), plus `truncate('login_attempts')`/`truncate('users')` in `setUp()`. Migrations therefore run on `snak_test` only; `snak` is never touched (its only test access stays the read-only `/health` check from change 1).
- A missing `snak_test` throws on connect — the suite fails, it does not skip.

**`tests/_support/AuthTestTrait.php`** (namespace `Tests\Support`) captures two CI4 4.7 facts proven by probe:

```php
protected function continueSession(): void { $this->withSession($_SESSION); }

protected function postWithCsrf(string $path, array $params = []): TestResponse
{
    $this->continueSession();
    $params[config('Security')->tokenName] = csrf_hash();
    return $this->post($path, $params);
}
```

`FeatureTestTrait` resets `$_SESSION = $this->session` on **every** call, so state set by one request is invisible to the next unless snapshotted. And because `Security::$regenerate` rotates the hash after each verified POST, the token must be read immediately before each POST — never reused.

Assertions that differ from the naive form:

- CSRF rejection: `$this->expectException(SecurityException::class)` around the POST. In non-production `$redirect` is false, so the filter rethrows; there is no status code to assert. `/login` via `GET` is unaffected (only POST/PUT/DELETE/PATCH are verified).
- Session ID regeneration: `assertTrue(service('session')->didRegenerate)` (`MockSession` sets it). Do not compare `session_id()` values.
- Flash copy: `assertSessionHas('authError', lang('Auth.loginFailed'))` on the POST, then a separate `POST`-free render test with `withSession(['authError' => lang('Auth.loginFailed')])` asserting the SweetAlert2 call. Flash data does not age in `MockSession`, so never rely on "shown once" in tests.
- `staff:admin` and the multi-segment intended URL (`/cashier/products`) have no production routes; those tests use `withRoutes([['GET', 'cashier/products', static fn () => 'x', ['filter' => 'staff:cashier']], ...])` (4th element = options), so no fake admin surface is added.

### D9 — Login view

`app/Views/auth/login.php`, `$this->extend('layouts/base')` + `section('content')` — same pattern as `home/index.php`; no layout change, no new assets.

- Bootstrap card, `#003366` primary button, `<form method="post" action="/login">` with `csrf_field()`.
- Field errors inline (`is-invalid` + `.invalid-feedback`) for empty username/password, sourced from `Auth.usernameRequired` / `Auth.passwordRequired`.
- Invalid credentials / throttle / session expiry → **SweetAlert2** from `section('pageScripts')`:
  `Swal.fire({icon:'error', title: <?= json_encode($alert) ?>, confirmButtonColor:'#003366'})`.
- DataTables is loaded by the layout but never invoked here — asserted (`assertStringNotContainsString('DataTable', $body)`) as evidence for the relaxed `app-bootstrap` rule.

**Why the alert and not only inline errors**: the `app-bootstrap` delta explicitly permits SweetAlert2 "for login alerts"; the generic credential failure is not attached to a single field (attaching it to one would hint at which field is wrong), so a modal is the correct surface. Inline errors stay for actionable field-level mistakes.

Logout is a `POST /logout` form with `csrf_field()` in `cashier/index.php` — never a link, so a GET cannot end the session.

### D10 — Work units

Three feature slices as proposed; **five commits**, because two slices exceed 400 authored lines as single units. Tests ship with the code they prove.

| # | Commit | Files | ~Lines |
|---|---|---|---|
| 1a | `feat(auth): add staff users and login-attempt schema` | 2 migrations, `UserModel`, `Exceptions/ValidationException`, `AuthService` (`createUser`, `hashPassword`, normalize), `Config/Services`, `Config/Database` tests group, `tests/bootstrap.php`, `phpunit.dist.xml` bootstrap, `.env.example`, `.gitignore`, `tests/database/UserModelTest.php` | **~330** |
| 1b | `feat(cli): add user:create command` | `Commands/UserCreate.php`, `tests/database/UserCreateCommandTest.php` | **~150** |
| 2a | `feat(auth): add staff login and logout` | `AuthService` (`attempt`, `logout`, `isAuthenticated`), `AuthController`, `Views/auth/login.php`, `Language/es/Auth.php`, `Config/Filters` (csrf), `Config/Session`, `.env.example` cookie line, `tests/_support/AuthTestTrait.php`, `tests/feature/AuthTest.php` | **~390** |
| 2b | `feat(auth): throttle failed logins` | `LoginThrottleService`, 2-line wiring in `attempt()`, `tests/database/LoginThrottleServiceTest.php`, throttle cases appended to `AuthTest`, `tests/unit/AuthLanguageTest.php` | **~225** |
| 3 | `feat(auth): gate cashier area by role` | `Filters/StaffFilter.php`, `AuthService::currentUser`, `Config/Routes`, `Controllers/CashierController.php`, `Views/cashier/index.php`, `Views/errors/403.php`, `tests/feature/StaffFilterTest.php` | **~250** |

PR 1 = 1a+1b, PR 2 = 2a+2b, PR 3 = 3; stacked-to-main, in order.

**Flags**:
- Slice 1 as originally proposed was ~480 lines (schema + model + command + two test files). Splitting at the CLI seam brings both halves under budget without breaking autonomy — 1b's tests are service-level and do not need the HTTP stack.
- Slice 2 as a single commit is **~615**, well over 400. The split above puts the throttle (service + its tests + 2 feature cases) in 2b.
- **2a intentionally ships login without rate limiting**, because `attempt()` cannot call a service that does not exist yet. PR 2 must merge 2b before the feature is called done; only 2b's 2-line wiring closes the gap. If a reviewer rejects intermediate security debt, the alternative is `size:exception` on a ~615-line 2a — worse for review.
- 2a lands at ~390 only if `AuthLanguageTest` moves to 2b (done). If the view grows, move `AuthTest`'s CSRF block to 2b.

## Data Flow

```
GET /cashier
  └─ csrf (GET: no-op) → staff:cashier
       ├─ no user_id ──→ set intended_url → 302 /login
       ├─ row missing / is_active = 0 ──→ clear + destroy → 302 /login (+flash)
       └─ role ∉ args ──→ 403 errors/403
            └─ ok ──→ CashierController::index → currentUser() → cashier/index

POST /login
  └─ csrf (verify → rotate token) → AuthController::login
       ├─ empty fields → 302 back + inline errors
       ├─ throttle blocked → dummy verify → 302 /login + Auth.loginFailed
       ├─ verify ok, active → resetWindow → regenerate(true) → user_id
       │                       → last_login_at → 302 intended_url | /cashier
       └─ else → recordFailure → 302 /login + Auth.loginFailed
```

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Database/Migrations/2026-10-21-100000_CreateUsers.php` | Create | `users` |
| `app/Database/Migrations/2026-10-21-100100_CreateLoginAttempts.php` | Create | `login_attempts` + 2 indexes |
| `app/Models/UserModel.php` | Create | lookup, `createStaff`, `setActive`, role/active not mass-assignable |
| `app/Services/AuthService.php` | Create | credentials, session, intended URL, creation |
| `app/Services/LoginThrottleService.php` | Create | window count, reset, purge |
| `app/Exceptions/ValidationException.php` | Create | error bag for creation |
| `app/Commands/UserCreate.php` | Create | `user:create`, hidden password |
| `app/Filters/StaffFilter.php` | Create | auth + active + role gate |
| `app/Controllers/AuthController.php`, `CashierController.php` | Create | login/logout; placeholder landing |
| `app/Views/auth/login.php`, `cashier/index.php`, `errors/403.php` | Create | Spanish UI |
| `app/Language/es/Auth.php` | Create | all copy |
| `app/Config/{Filters,Routes,Services,Session,Database}.php` | Modify | csrf + `staff`, routes, DI, regen-destroy, `tests` group |
| `phpunit.dist.xml`, `.env.example`, `.gitignore` | Modify | bootstrap, test DB keys, `.env.testing` |
| `tests/bootstrap.php`, `tests/_support/AuthTestTrait.php` | Create | `.env.testing`, session/CSRF helpers |
| `tests/database/{UserModel,UserCreateCommand,LoginThrottleService}Test.php` | Create | DB layer |
| `tests/feature/{Auth,StaffFilter}Test.php`, `tests/unit/AuthLanguageTest.php` | Create | behaviour |
| `composer.json`, `composer.lock`, `Config/{App,Cookie,Security}.php` | — | **unchanged** (verified: locale already `es`, httponly/Lax already correct) |

## Migration / Rollout

1. `php spark migrate --all` on `snak`; `migrate:status` shows both; `migrate:rollback` drops both (acceptance for the `app-bootstrap` delta).
2. Provision `snak_test` with the same grants and create `.env.testing` (`database.tests.username/password`) before PR 1's tests run; also add the keys to `snak`'s `.env` if `.env.testing` is skipped.
3. `php spark user:create` for the first cashier — password typed, never stored in a file.
4. Production `.env`: `cookie.secure = true` (HTTPS) — apply before exposing `/login`.

Rollback: revert the commits, `php spark migrate:rollback`. No production data exists.

## Open Questions

- [ ] `is_active` (orchestrator lock) vs `active` (spec prose) — documented as the same attribute; confirm verify does not require the literal column name `active`.
- [ ] `cookie.secure=true` in production cannot be asserted by CI; it is an `.env` checklist item, verified manually before deploy.
- [ ] `snak_test` credentials/grants are operational and unverified from this repo — the suite fails loudly if absent.
- [ ] 2a ships without throttling (see D10) — accept the stacked-PR intermediate state, or take `size:exception`.
