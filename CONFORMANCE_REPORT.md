# Conformance Report — Recheck

**Extension:** t3g/blog v14.0.0  
**Date:** 2026-03-11

## Scoring

| Category | Score | Max | Notes |
|----------|-------|-----|-------|
| Architecture | 18 | 20 | Project structure and namespaces are consistent |
| Coding Guidelines | 16 | 20 | strict types now consistent in active code paths |
| PHP Quality | 14 | 20 | PHPStan still reports nullable rendering context and test array-offset issues |
| Testing | 16 | 20 | unit + functional suites exist; current unit run has regressions |
| Best Practices | 16 | 20 | DDEV + composer scripts + static analysis workflow in place |
| **Total** | **80** | **100** | **Grade: A — Production Ready with follow-up fixes** |

## Recheck Findings

### High — Nullable rendering context handling in ViewHelpers

PHPStan reports `method.nonObject` for these files:

- `Classes/ViewHelpers/CacheViewHelper.php`
- `Classes/ViewHelpers/Link/ArchiveViewHelper.php`
- `Classes/ViewHelpers/Link/AuthorViewHelper.php`
- `Classes/ViewHelpers/Link/CategoryViewHelper.php`
- `Classes/ViewHelpers/Link/PostViewHelper.php`
- `Classes/ViewHelpers/Link/TagViewHelper.php`

**Required change:** guard nullable `$this->renderingContext` before method calls.

### Medium — Functional test assertions access nullable arrays

`Tests/Functional/Hooks/DataHandlerHookWorkspaceTest.php` indexes arrays returned
by `BackendUtility::getRecord*()` without asserting non-null first.

**Required change:** assert array shape before offset access.

### Medium — Unit test type references no longer match TYPO3 core

`Tests/Unit/ExpressionLanguage/BlogVariableProviderTest.php` mocks
`TYPO3\CMS\Core\Routing\PageInformation`, which is not available in the current
dependency graph.

**Required change:** use a lightweight test double with `getPageRecord()`.

## Planned Conformance Change Set

1. Add nullable-rendering-context guards in affected ViewHelpers
2. Harden workspace functional tests with explicit non-null assertions
3. Update BlogVariableProvider unit test doubles for current TYPO3 APIs
