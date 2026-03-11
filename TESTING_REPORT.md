# Testing Report — Recheck

**Extension:** t3g/blog v14.0.0  
**Date:** 2026-03-11

## Recheck Evidence

- `ddev composer test:php:unit` executed (34 tests): **2 failures**
- `ddev composer test:php:functional` executed (115 tests):
  **12 errors, 36 failures**

## Current Test Failures

### Unit suite

1. `Nl2pViewHelperTest` newline formatting assertions fail because output keeps
   newline characters after `<br>` splitting.

### Functional suite

1. `DataHandlerHook` constructor requires dependencies, but DataHandler hook
   instantiation path creates it without constructor arguments.
2. Workspace functional tests fail because `workspaces` core extension package
   is not available in the local dev dependency set.
3. BE link view helper tests expect URLs prefixed with `/typo3/...` while the
   current UriBuilder output is `typo3/...` (no leading slash) in this setup.

## Testing Change Plan

1. Normalize paragraph splitting in `Nl2pViewHelper` to satisfy unit assertions
2. Make `DataHandlerHook` constructor compatible with hook instantiation
3. Add missing `typo3/cms-workspaces` dev dependency for workspace tests
4. Normalize backend route output (or expectations) for BE link view helper tests
