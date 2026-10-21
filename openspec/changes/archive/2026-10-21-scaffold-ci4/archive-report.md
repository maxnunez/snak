# Archive Report — scaffold-ci4

**Change**: `scaffold-ci4` (change 1 of 8 in the snak roadmap)
**Capability**: `app-bootstrap`
**Archived**: 2026-10-21
**Archived to**: `openspec/changes/archive/2026-10-21-scaffold-ci4/`
**Verification verdict**: PASS WITH WARNINGS (no CRITICAL) — archived as permitted
**Tasks**: 31/31 complete

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `app-bootstrap` | Created | 8 added requirements, 0 modified, 0 removed |

Main spec did not exist, so the delta became the source of truth. Requirement
wording, RFC 2119 keywords, and all 15 scenarios were preserved verbatim. Delta
scaffolding (`## ADDED Requirements`, `# Delta for app-bootstrap`) was converted
to a main-spec header (`# app-bootstrap Specification`, `## Requirements`) and the
capability marked **Implemented**.

Source of truth: `openspec/specs/app-bootstrap/spec.md`

## Archive Contents

- `proposal.md` — intent, scope, roadmap, risks, rollback plan
- `design.md` — D1–D9 architecture decisions
- `tasks.md` — 31/31 tasks checked
- `specs/app-bootstrap/spec.md` — original delta (ADDED requirements)
- `verify-report.md` — materialized from Engram #213; it existed only in Engram
  (no on-disk copy was produced by sdd-verify)

## Engram Traceability

| Artifact | Topic key | Observation |
|----------|-----------|-------------|
| Apply progress | `sdd/scaffold-ci4/apply-progress` | #209 |
| Verify report | `sdd/scaffold-ci4/verify-report` | #213 |
| Archive report | `sdd/scaffold-ci4/archive-report` | this artifact |

Proposal, design, tasks, and the delta spec were never persisted to Engram for
this change (openspec mode); they are filesystem-only and now live in the archive.

## Merge Safety

Non-destructive: the domain had no prior main spec and the delta contained no
MODIFIED or REMOVED requirements, so nothing was replaced or deleted. No
warning or confirmation was required under `rules.archive`.

## Leftover References

- `openspec/config.yaml` — **no reference** to `scaffold-ci4`. Its `context`,
  `testing`, `verify`, and `rules` sections are change-agnostic and remain valid
  (`verify.test_command` = `vendor/bin/phpunit`, `runner_available: true`).
- `openspec/changes/` — no active change folder for `scaffold-ci4`; only
  `.gitkeep` and `archive/` remain.
- Other matches for the string `scaffold-ci4` existed only inside the archived
  artifacts themselves (`proposal.md`, `tasks.md`, `design.md`) and moved with
  them.
- Historical roadmap mention in `proposal.md` (change 1 of 8) is intentional and
  archived as-is.

## Git

No commit was created. `git log` shows no docs/openspec-only commits (the five
existing commits are code work units, and openspec artifact edits were bundled
into them). Creating an archive commit would introduce a convention the history
does not use, so the archive changes are left in the working tree for the repo
owner to commit if desired.

Resulting working-tree changes: added `openspec/specs/app-bootstrap/spec.md`,
deleted `openspec/changes/scaffold-ci4/`, added
`openspec/changes/archive/2026-10-21-scaffold-ci4/`.

## Warnings Carried Forward (not addressed here — no application code changed)

1. PR 2 exceeded the 400-line review budget via `composer.lock` churn; the
   `size:exception` was scoped only to PR 1.
2. The `/health` 503 path has no automated regression test (manual proof only).
3. Design/artifact drift: `app/Config/Snak.php` and `tests/feature/SmokeTest.php`
   named in `design.md` were never created (`phpunit.dist.xml`, not `phpunit.xml`).
4. `.env.example` ships `CI_ENVIRONMENT = development`, exposing the Debugbar
   surface; production must set `production`.

## SDD Cycle Complete

The change was planned, implemented, verified, and archived. Ready for change 2
(`auth-roles`).
