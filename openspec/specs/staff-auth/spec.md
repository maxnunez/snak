# staff-auth Specification

**Status**: Implemented
**Source change**: `auth-roles` (archived 2026-10-21)
**Verification**: PASS WITH WARNINGS (no CRITICAL)
**Test group**: `tests` (PHPUnit, database `snak_test`)

## Requirements

### Requirement: Staff Credential Storage

`users` MUST hold `id`, unique `username`, `password_hash`, `role`, `active`, `last_login_at`, and timestamps. `role` MUST be `cashier` or `admin`. `role` and `active` MUST NOT be mass-assignable from web input.

#### Scenario: Staff record persists

- GIVEN a valid username and password
- WHEN a staff record is created
- THEN it stores a hashed password, an active flag, and a role
- AND `last_login_at` is null before first login

#### Scenario: Duplicate username rejected

- GIVEN a username already exists
- WHEN another record uses it
- THEN validation and the unique index both reject it

### Requirement: Password Hashing Policy

Passwords MUST use `password_hash(PASSWORD_DEFAULT)` and `password_verify`; plaintext or reversible storage MUST NOT occur. Length MUST be 8 to 72 bytes.

#### Scenario: Round-trip verification

- GIVEN a password within policy
- WHEN it is hashed and verified
- THEN verification succeeds and the stored value differs from plaintext

#### Scenario: Length policy enforced

- GIVEN a password under 8 characters or over 72 bytes
- WHEN it is submitted
- THEN creation fails with a Spanish validation message

### Requirement: Login Success

Valid credentials of an active user MUST create a session, regenerate its ID, update `last_login_at`, and redirect to the intended URL or `/cashier`.

#### Scenario: Cashier logs in

- GIVEN an active cashier with valid credentials
- WHEN `POST /login` carries them with a valid CSRF token
- THEN the session ID changes and the redirect targets `/cashier`
- AND `last_login_at` is set

#### Scenario: Intended URL restored

- GIVEN a request to `/cashier/products` was redirected to `/login`
- WHEN the cashier logs in
- THEN the redirect target is `/cashier/products`

### Requirement: Login Failure Without Enumeration

Unknown username, wrong password, and inactive account MUST yield one identical generic Spanish message and MUST NOT create a session. Unknown usernames MUST still run a verification against a dummy hash.

#### Scenario: Wrong password

- GIVEN an active cashier
- WHEN a wrong password is submitted
- THEN no session is created and the generic message is shown

#### Scenario: Unknown user indistinguishable

- GIVEN no account exists for the username
- WHEN login is attempted
- THEN the message and status equal the wrong-password case
- AND a dummy verification is executed

### Requirement: Inactive User Rejection

An inactive user MUST NOT authenticate, and a user deactivated mid-session MUST be rejected on the next protected request.

#### Scenario: Inactive at login

- GIVEN an inactive account with correct credentials
- WHEN login is attempted
- THEN it is refused with the generic message

#### Scenario: Deactivated mid-session

- GIVEN a logged-in cashier is deactivated
- WHEN the next protected request arrives
- THEN the session is destroyed and the request redirects to `/login`

### Requirement: Logout

`POST /logout` MUST require a valid CSRF token, destroy the session, and redirect to `/login`. A repeat of the previous protected request MUST NOT restore access.

#### Scenario: Logout destroys session

- GIVEN an authenticated cashier
- WHEN `POST /logout` is submitted with a valid token
- THEN the session is destroyed and the redirect targets `/login`
- AND the next protected request redirects to `/login`

### Requirement: Login Throttling

Failed attempts MUST be counted per username and IP in `login_attempts`. The 6th failure within 15 minutes MUST be refused even with the correct password. A successful login MUST reset that key's counter.

#### Scenario: Sixth attempt blocked

- GIVEN 5 failures for one username and IP within 15 minutes
- WHEN the correct password is submitted
- THEN the request is refused with the generic message and no session

#### Scenario: Window expires

- GIVEN 5 recorded failures older than 15 minutes
- WHEN login is attempted
- THEN the attempt is evaluated normally

### Requirement: Session and Cookie Hardening

The session ID MUST regenerate on login and the session MUST be destroyed on logout. Session cookies MUST set `httponly` and `samesite=Lax`, SHOULD set `secure` outside local development, and MUST NOT contain credentials.

#### Scenario: Secure flags per environment

- GIVEN production configuration
- WHEN the session cookie is issued
- THEN it carries `httponly`, `samesite=Lax`, and `secure`
- AND local HTTP development may omit `secure`

### Requirement: CSRF on Authentication Endpoints

The `csrf` filter MUST apply globally to state-changing verbs. `POST /login` and `POST /logout` MUST reject a missing or invalid token without changing session state.

#### Scenario: Missing token rejected

- GIVEN an unauthenticated client
- WHEN `POST /login` is sent without a token
- THEN the request is rejected and no session is created

#### Scenario: Logout token required

- GIVEN an authenticated cashier
- WHEN `POST /logout` carries an invalid token
- THEN the session remains intact

### Requirement: First Cashier Account Command

`php spark user:create` MUST prompt for username and password, offer `cashier` or `admin` with `cashier` as default, hash the password, and refuse duplicate usernames. It MUST NOT echo, log, or persist a plaintext password, and no default credential MAY exist in the repository.

#### Scenario: Creates an active cashier

- GIVEN the database is migrated
- WHEN the command runs with a new username and valid prompted password
- THEN an active `cashier` exists with a hashed password

#### Scenario: Duplicate refused

- GIVEN the username already exists
- WHEN the command runs
- THEN it exits non-zero and writes nothing

#### Scenario: No credential in repository

- WHEN tracked files are scanned
- THEN no default username/password pair or seeded credential exists

### Requirement: Spanish Copy via Language Files

Login copy and alerts MUST come from `app/Language/es/Auth.php`. Views MUST NOT hold user-facing literals. Tests MUST assert language keys, not rendered prose. Identifiers and comments MUST be English.

#### Scenario: Keys resolve

- GIVEN the `es` locale is active
- WHEN the login view renders
- THEN each message is produced by an `Auth.*` key

#### Scenario: Key presence asserted

- WHEN the language test runs
- THEN every key used by the login view and controller exists in `Auth.php`

## Non-Functional Requirements

- `users.username` MUST have a unique index; `login_attempts` MUST be indexed by `(username, ip_address, attempted_at)`.
- Attempts older than 24 hours MUST be purged; purging MUST NOT delete rows inside the active 15-minute window.
- Passwords, hashes, tokens, and session IDs MUST NOT appear in logs, exception pages, or responses.
- No credential MAY appear in tracked files; DB credentials live only in `.env` and `.env.testing`.
- Every scenario above MUST have a PHPUnit test in the `tests` group running against `snak_test`.
