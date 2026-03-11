# Conformance Report — TYPO3 Extension Standards

**Extension:** t3g/blog v14.0.0
**Date:** 2026-03-11

## Scoring

| Category | Score | Max | Notes |
|----------|-------|-----|-------|
| Architecture | 18 | 20 | Good structure, proper namespacing, Services.yaml present |
| Coding Guidelines | 10 | 20 | 86 files with wrong strict_types format, missing final, no constructor promotion |
| PHP Quality | 14 | 20 | Untyped properties in models, 20+ files with makeInstance instead of DI |
| Testing | 15 | 20 | Existing unit + functional tests, but no workspace tests, no architecture tests |
| Best Practices | 14 | 20 | CI configured, PHPStan configured, but missing coverage reporting |
| **Total** | **71** | **100** | **Grade: B — Development Ready** |

## Findings

### Critical (86 files): `declare(strict_types = 1)` spacing
All but 2 files use `declare(strict_types = 1)` with spaces. PSR-12/PER requires `declare(strict_types=1)` without spaces.

### High (45+ classes): Missing `final` keyword
Most concrete classes lack `final`. Only classes designed for inheritance (abstract, base controllers extending Extbase) should omit it.

### High (14 classes): Missing constructor property promotion
Controllers, services, and listeners manually assign `$this->x = $x` instead of using PHP 8.0+ constructor promotion.

### Medium (18 properties): Untyped properties
Domain models (Comment, Category, Post) have untyped properties with `@var` annotations.

### Medium (2 files): Missing `declare(strict_types=1)`
- `Classes/TitleTagProvider/BlogTitleTagProvider.php`
- `Classes/Updates/FeaturedImageUpdate.php`

### Low (5 files): Useless `/** Class FooBar */` comments
Classes with doc comments that just repeat the class name.

## Action Items
1. Fix strict_types declaration format across all 86 files
2. Add `final` to non-inheritable classes
3. Apply constructor property promotion to controllers, services, listeners
4. Add missing strict_types declarations to 2 files
5. Remove useless class doc comments
