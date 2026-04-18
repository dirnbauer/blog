# Testing Audit — Gaps and Quick Wins

_Date: 2026-04-18_
_Skill: typo3-testing_

## Inventory

| Item | State |
| --- | --- |
| Unit tests | 514 cases (after legacy-TS cleanup), 1248 assertions, green |
| Functional tests | Present in `Tests/Functional/{AvatarProvider, Domain, Hooks, Rendering, Service, Updates, ViewHelpers}` |
| Architecture tests (phpat) | ❌ not configured |
| Mutation tests (Infection) | ❌ not configured |
| Playwright E2E | 1 spec (`Tests/Playwright/blog-pages.spec.ts`) — silently skipped without `BLOG_BASE_URL` |
| PHPStan | level 9 + saschaegerer/phpstan-typo3, clean |
| PHP-CS-Fixer | clean |
| CI matrix | PHP 8.2 / 8.3 / 8.4, MariaDB 10.11 |
| `Build/Scripts/runTests.sh` | ❌ not present (only `run-functional-tests.php`) |
| `captainhook.json` / `.git/hooks/pre-commit` | ❌ not configured |
| Playwright wired into CI | ❌ (audit earlier) |

## Findings

### 1. No `Build/Scripts/runTests.sh` umbrella entry (MEDIUM)

The skill lists `runTests.sh` as mandatory. The repo has
`Build/Scripts/run-functional-tests.php`, which only covers the
functional suite. A thin shell wrapper that exposes `-s unit|functional|
phpstan|cgl` plus a `-p <php version>` flag is the accepted idiom for
TYPO3 extensions — it gives contributors a single command to run on
DDEV / native / CI.

**Fix (this cycle):** Add `Build/Scripts/runTests.sh` that dispatches to
`composer test:*` / `composer phpstan` / `composer cgl` so the same
sequence used by CI is reachable locally. Executable bit set.

### 2. No pre-commit / CaptainHook enforcement (LOW)

The conformance + security reviews already surfaced that most
developer workflows would benefit from a `captainhook.json` that runs
`composer cgl` + `phpstan` on staged files. Not added this cycle —
adding hooks without owner consensus is invasive.

### 3. No architecture tests (LOW)

The skill recommends `phpat` (Composer: `carlosas/phpat`) to assert
layer constraints (e.g. "Controllers never talk to ConnectionPool
directly"). The blog does not enforce any such rule today, but there
**are** text-grep "architecture" tests masquerading as unit tests:

- `Tests/Unit/Architecture/AllTemplatesWorkspaceSafetyTest.php:70`
- `Tests/Unit/Architecture/AllRepositoriesWorkspaceAwarenessTest.php:44`
- `Tests/Unit/Controller/BackendAuthorizationRegressionTest.php`

These use `file_get_contents` + `assertStringContainsString`. They
would be more robustly expressed as `phpat` rules. Deferred — the
text-grep approach works and the migration is effort-intensive.

### 4. Playwright spec silently skipped in CI (MEDIUM — carried)

`playwright.config.ts:12` falls back to `http://127.0.0.1:8000`;
`blog-pages.spec.ts:16` uses `test.skip(!baseUrl, …)`. CI
(`.github/workflows/ci.yml:93-113`) has a `frontend-build` job that
runs `npm run build` but never invokes Playwright. The spec is dead
weight in CI.

**Fix (this cycle):** Add an `e2e` job scaffold that documents the
expected environment (`BLOG_BASE_URL`) and runs Playwright when it is
set. If the env var is missing, skip loudly (`echo "skipping e2e: set
BLOG_BASE_URL"` + `exit 0`) rather than silently passing. Full CI
coverage (spinning up TYPO3 + DB) is a separate infrastructure task.

### 5. Functional test bootstrap could assert setup state (LOW)

`Tests/Functional/SiteBasedTestCase.php` is thin. It sets up a site
with `SiteWriter`. Functional tests that need a workspace-enabled
context currently each bootstrap their own workspace — a shared helper
(`WorkspaceAwareFunctionalTestCase`) would remove duplication and
match the pattern recommended in the typo3-workspaces skill.

**Fix (deferred):** Introduce the helper once a second workspace
functional test is added — otherwise it is over-engineering for one
call site.

### 6. PHPStan level 9 (OK — skill asks for level 10)

The skill's scoring mentions level 10 for full marks. Level 9 already
enforces "everything but `mixed`-in-mixed-out arrays", which is a very
high bar and currently clean. Level 10 adds "no `mixed` anywhere" and
would re-open many TCA-access paths that legitimately return `mixed`.
Keep level 9 + TYPO3 extension rules.

### 7. Coverage is not measured (LOW)

Neither `Build/UnitTests.xml` nor `Build/FunctionalTests.xml` defines a
`coverage` section. `composer.json` has no `composer test:coverage`
script. A one-time measurement would let the follow-up skill cycles
target the specific untested classes highlighted in the initial review
(PostController, CommentController, NotificationManager, …).

**Fix (this cycle):** Add coverage output configuration (clover +
HTML) to `Build/UnitTests.xml` and a convenience script
`composer test:php:coverage` so `Build/Scripts/runTests.sh` can
dispatch `-s coverage`.

## Planned changes (this cycle)

1. Add `Build/Scripts/runTests.sh` with suite + PHP version flags.
2. Add `coverage` section to `Build/UnitTests.xml` + a
   `test:php:coverage` composer script.
3. Add a loud-skip `e2e` job scaffold to `.github/workflows/ci.yml`
   (no browser runtime yet; environment-gated).

Deferred:

- phpat architecture tests (finding #3).
- Infection mutation testing (capacity item, separate cycle).
- CaptainHook pre-commit (finding #2; needs consensus).
- `WorkspaceAwareFunctionalTestCase` helper (finding #5; too early).
