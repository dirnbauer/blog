# Extension Upgrade Report — v13/v14 Compatibility

**Extension:** t3g/blog v14.0.0
**Date:** 2026-03-11
**Scope:** Deprecated APIs, version constraint issues, v14 breaking changes

## 1. Critical — v14 Breaking Changes

### 1.1 `$GLOBALS['TSFE']` usage (removed in v14)

| File | Lines | Current Code |
|------|-------|-------------|
| `Classes/ExpressionLanguage/BlogVariableProvider.php` | 22, 31 | `$GLOBALS['TSFE']->page ?? []` |

**Fix:** Use `$GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')` to get page data. The `ConditionProvider` passes the variable provider; we need to make the request accessible.

### 1.2 `searchFields` in TCA ctrl (Breaking #106972 in v14)

| File | Current |
|------|---------|
| `Configuration/TCA/tx_blog_domain_model_tag.php` | `'searchFields' => 'uid,title'` |
| `Configuration/TCA/tx_blog_domain_model_author.php` | `'searchFields' => 'uid,name,title'` |
| `Configuration/TCA/tx_blog_domain_model_comment.php` | `'searchFields' => 'uid,comment,name,email'` |

**Fix:** Keep `searchFields` for v13 compat, but also add `'searchable' => true` on relevant columns for v14. Both coexist safely.

## 2. High — Version Constraint Mismatch

### 2.1 ext_emconf.php

`ext_emconf.php` declares `typo3: 13.4.15-13.4.99` but `composer.json` supports `^13.4.15 || 14.*.*@dev`.

**Fix:** Update ext_emconf.php to `13.4.15-14.99.99`.

## 3. Medium — Deprecated Patterns

### 3.1 `canNotCollapse` in TCA palettes

| File | Line |
|------|------|
| `Configuration/TCA/tx_blog_domain_model_tag.php` | 167 |
| `Configuration/TCA/tx_blog_domain_model_comment.php` | 144 |

Deprecated since TYPO3 7.4. Safe to remove — has no effect.

### 3.2 `l18n_parent` field naming convention

The tag and author tables use `l18n_parent` as the translation pointer column name. While this still works (it is an arbitrary column name), the TYPO3 core convention since v11 is `l10n_parent`. Renaming requires a database column rename — **not recommended** at this point as it is purely cosmetic and would require a migration wizard.

**Decision:** Keep as-is. Not a breaking change.

### 3.3 `ext_tables.sql` manual column definitions

The `tx_blog_domain_model_comment` table defines columns that TYPO3 auto-manages (`uid`, `pid`, `tstamp`, `crdate`, `cruser_id`, `sorting`, `deleted`, `hidden`). These should be removed from `ext_tables.sql` since TYPO3 v13+ creates them automatically from TCA `ctrl`.

### 3.4 Legacy DataHandler hook keys

`ext_localconf.php` uses `SC_OPTIONS['t3lib/class.t3lib_tcemain.php']['processDatamapClass']`. This hook path is still supported in v14 but the class path prefix is legacy. No breaking change.

### 3.5 `GeneralUtility::makeInstance()` for DI-capable services

20+ files use `GeneralUtility::makeInstance()` for services that should be constructor-injected. Most impactful:
- `BlogVariableProvider` — no DI (instantiated via `ConditionProvider`)
- `DataHandlerHook` — uses `GeneralUtility::makeInstance(CacheService::class)` inline
- `CommentFormFinisher` — instantiates 7+ services via makeInstance
- `AvatarProvider/*` — manual service creation

**Fix scope:** Address the most critical ones (DataHandlerHook, BlogVariableProvider). Full DI migration of all 20+ files is out of scope for this upgrade pass.

## 4. Low — Cosmetic Issues

### 4.1 PHPDoc class comments on several classes

Multiple classes have single-line `/** Class FooBar */` doc comments that add no value.

### 4.2 Empty eval strings

Several TCA columns have `'eval' => ''` which is redundant.

## 5. Summary of Required Changes

| Priority | Change | Files |
|----------|--------|-------|
| CRITICAL | Fix `$GLOBALS['TSFE']` in BlogVariableProvider | 1 file |
| HIGH | Update ext_emconf.php version constraints | 1 file |
| HIGH | Add `searchable` to TCA columns for v14 | 3 TCA files |
| MEDIUM | Remove `canNotCollapse` from palettes | 2 TCA files |
| MEDIUM | Remove redundant columns from ext_tables.sql | 1 file |
| MEDIUM | Inject CacheService/ConnectionPool in DataHandlerHook | 1 file |
| LOW | Remove empty `eval` strings | 3 TCA files |
| DEFERRED | Full DI migration across all 20+ files | Phase 2 (conformance) |
