# SDD Status — snak

**Updated**: 2026-10-21
**Artifact store**: openspec (files) + Engram (reports and progress)
**Active changes**: none

## Archived changes

| Change | Verdict | Commit range | Notes |
|--------|---------|--------------|-------|
| `scaffold-ci4` → [`archive/2026-10-21-scaffold-ci4/`](changes/archive/2026-10-21-scaffold-ci4/) | PASS WITH WARNINGS | `2f7d856..4c2bca8` | Change 1 of 8. CI4 4.7.5 skeleton, DB config, `/health`, PHPUnit, base shell, PSR-12. 31/31 tasks. Warnings: `/health` 503 path manual-only, env/debugbar hardening, artifact drift. |
| `auth-roles` → [`archive/2026-10-21-auth-roles/`](changes/archive/2026-10-21-auth-roles/) | PASS WITH WARNINGS | `cb37510..d4e68f2` | Change 2 of 8. Staff auth, `cashier`/`admin` roles, CSRF, throttling, `staff` filter, `user:create`, Spanish copy. 42/42 tasks; 51 tests / 241 assertions. Promoted `staff-auth` + `access-control`; modified 3 `app-bootstrap` requirements. Warnings: PR slices >400 lines, empty `coverage_command`, `UserCreate` TTY coverage. |

## Backlog

Changes 3–8, in roadmap order; none started.

1. `products-catalog` — change 3
2. `reservation-window` — change 4
3. `student-reservations` — change 5
4. `cash-register-opening` — change 6
5. `pos-collections` — change 7
6. `reports` — change 8

## Source of truth

- `openspec/specs/app-bootstrap/spec.md`
- `openspec/specs/staff-auth/spec.md`
- `openspec/specs/access-control/spec.md`
