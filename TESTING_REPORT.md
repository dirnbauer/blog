# Testing Report — Coverage Analysis

**Extension:** t3g/blog v14.0.0
**Date:** 2026-03-11

## Inventory

- **Unit tests:** 5 files, ~14 test methods
- **Functional tests:** 26 files, ~41+ test methods
- **PHPUnit config:** Build/UnitTests.xml, Build/FunctionalTests.xml (both PHPUnit 9.3 schema)
- **Testing framework:** typo3/testing-framework ^9.0.0

## Well-Covered Areas

- All 9 upgrade wizards (functional)
- CommentService (unit)
- GravatarUriBuilder, GravatarResourceResolver (unit)
- GravatarProvider (functional)
- SetupService (functional)
- All Link ViewHelpers — frontend and backend (functional)
- DataHandlerHook — basic create/update (functional)

## Critical Gaps

| Component | Gap | Priority |
|-----------|-----|----------|
| DataHandlerHook — workspace | `isWorkspacePlaceholder()` and `workspace > 0` early return not tested | HIGH |
| Nl2pViewHelper | XSS fix (htmlspecialchars) not covered | HIGH |
| BackendController | No tests at all | MEDIUM |
| GoogleCaptchaValidator | No tests (logic bug was fixed) | MEDIUM |
| BlogVariableProvider | No tests ($GLOBALS['TSFE'] replacement) | MEDIUM |
| Workspace scenarios | No fixtures with t3ver_* fields | HIGH |

## Action Items

1. Add functional tests for DataHandlerHook workspace behavior
2. Add unit test for Nl2pViewHelper XSS escaping
3. Add unit test for BlogVariableProvider
4. Add functional tests for workspace record visibility
5. Update PHPUnit XML schema from 9.3 to match testing-framework ^9.0
