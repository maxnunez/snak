# Archive Report — auth-roles

**Change**: `auth-roles` (change 2 of 8 in the snak roadmap)
**Capabilities**: `staff-auth` (new), `access-control` (new), `app-bootstrap` (modified)
**Archived**: 2026-10-21
**Archived to**: `openspec/changes/archive/2026-10-21-auth-roles/`
**Verification verdict**: PASS WITH WARNINGS (no CRITICAL) — archived as permitted
**Tasks**: 42/42 complete (including VF.1–VF.10)

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `staff-auth` | Created | 11 added requirements, 0 modified, 0 removed |
| `access-control` | Created | 6 added requirements, 0 modified, 0 removed |
| `app-bootstrap` | Updated | 0 added, 3 modified (`Environment and Secret Isolation`, `Migration Infrastructure`, `Base UI Shell with Local Assets`), 0 removed |

`staff-auth` and `access-control` had no main spec, so their deltas became the
source of truth. Requirement wording, RFC 2119 keywords, scenarios, and
non-functional requirements were preserved verbatim. Delta scaffolding
(`**Change**: ... (new capability)`, `# <domain> Specification`) was converted to
main-spec headers with **Status: Implemented**, source change, and verdict.

`app-bootstrap` was merged by requirement name against the existing main spec:
the three modified requirements were replaced with their new wording and the
previously-only-in-delta scenarios (`Test database isolated`, updated `Fresh
migrate`, `Rollback removes domain tables`, `Library usage boundary`) were added.
The other five requirements (`Composer-Installed CI4 Skeleton`, `Health Endpoint`,
`Test Runner`, `Language Rules`, `PHP 8.4 Compatibility`) were left untouched. The
spec header now lists both source changes and the relaxed library/migration bounds.

Source of truth: `openspec/specs/staff-auth/spec.md`,
`openspec/specs/access-control/spec.md`, `openspec/specs/app-bootstrap/spec.md`

## Archive Contents

- `proposal.md` — intent, scope, capability deltas, D1–D4 rationale, risks, rollback
- `design.md` — D1–D10 architecture decisions, data flow, file changes, rollout
- `tasks.md` — 42/42 tasks checked, grouped 1a/1b/2a/2b/3 + verify-fix + notice-delivery
- `specs/staff-auth/spec.md` — original delta (ADDED requirements)
- `specs/access-control/spec.md` — original delta (ADDED requirements)
- `specs/app-bootstrap/spec.md` — original delta (MODIFIED requirements only)
- `verify-report.md` — materialized from Engram #221 (no on-disk copy was produced
  by sdd-verify); archive note records the later WARNING 2 closure in `d4e68f2`

## Engram Traceability

| Artifact | Topic key | Observation |
|----------|-----------|-------------|
| Apply progress | `sdd/auth-roles/apply-progress` | #219 |
| Verify report | `sdd/auth-roles/verify-report` | #221 |
| Archive report | `sdd/auth-roles/archive-report` | this artifact |

Proposal, design, tasks, and the deltas were never persisted to Engram for this
change (openspec mode); they are filesystem-only and now live in the archive.

## Merge Safety

Non-destructive. The two new capabilities had no prior main spec, so nothing was
replaced. `app-bootstrap` had exactly the three requirements named by the delta;
each was replaced in place, the five unrelated requirements were preserved, and no
requirement was removed. No warning or confirmation was required under
`rules.archive`.

## Verification Notes Carried Forward

The verdict is PASS WITH WARNINGS. WARNING 2 (session-expired flash lost after
session teardown) was resolved after the report by `d4e68f2`: the filter now sets a
one-time `snak_auth_notice` cookie, `AuthController::showLogin()` consumes it, and
the Spanish `Auth.sessionExpired` message renders once (VF.9/VF.10; 51 tests / 241
assertions; live proof). Remaining warnings are process/coverage items, not spec
failures:

1. PR slices exceeded the 400-line review budget (`size:exception` still required
   by process; no remote exists, so all commits are on local `main`).
2. TDD table lacks SAFETY NET / REFACTOR columns; no pre-change safety-net run
   documented for five modified test files.
3. `openspec/config.yaml` `coverage_command` is still empty (coverage runs manually
   with Xdebug).
4. `UserCreate` line coverage 28.57% — interactive TTY paths are doubled by tests.
5. SUGGESTION: `app/Commands/UserCreate.php` uses spark group `'auth'` while
   `design.md` says `'snak'` (cosmetic; command still runs).
6. SUGGESTION: `staff-auth` requires `user:create` to offer `admin` while
   `access-control` says only `cashier` is provisioned. Implementation follows
   `staff-auth`: both roles are accepted, `admin` is provisioned only on explicit
   selection and grants no extra capability yet. Intent confirmed at archive.

## Git

All pending OpenSpec artifacts — the `scaffold-ci4` moves/deletions and this
change's promotion, archive move, reports, and `STATUS.md` — are recorded in a
single commit:

```text
docs(sdd): track openspec artifacts for scaffold-ci4 and auth-roles
```

Implementation commits for this change (no AI attribution, conventional types):

```text
cb37510  feat(auth): add staff users and login-attempt schema
5e34b44  feat(auth): add user:create staff command
d8a6a65  feat(auth): add staff login and logout
aa69001  feat(auth): throttle failed logins
5ad5e0b  feat(auth): gate cashier area by role
2fcbb1b  fix(auth): harden session teardown and credential verification
3c243f7  test(auth): assert cookie flags, filter wiring, and Auth key coverage
c912769  fix(cli): localize user:create validation errors and align test DB docs
c6f7288  docs(sdd): record auth-roles verify-fix batch
d4e68f2  fix(auth): deliver session-expired notice after session teardown
```

Range: `cb37510..d4e68f2` (10 commits). No application code was changed by this
archive phase.

## SDD Cycle Complete

The change was planned, implemented, verified, and archived. Ready for change 3
(`products-catalog`).
