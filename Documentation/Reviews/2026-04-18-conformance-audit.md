# Conformance Audit — TYPO3 v14 / TER Readiness

_Date: 2026-04-18_
_Skill: typo3-conformance_

## Scorecard

| Dimension | Status |
| --- | --- |
| Metadata (composer.json, ext_emconf.php) | ✅ aligned on v14 / PHP 8.2–8.4 / version `14.1.0` |
| Directory structure | ✅ Classes/, Configuration/, Resources/, Tests/ all present |
| `declare(strict_types=1)` in every `Classes/**/*.php` | ✅ (grep `-L` returns empty) |
| PSR-12 / php-cs-fixer | ✅ clean |
| PHPStan level 9 + saschaegerer/phpstan-typo3 | ✅ clean |
| No `$GLOBALS` outside documented TYPO3 access paths | ✅ only BE_USER / LANG / TCA / TYPO3_CONF_VARS / TYPO3_REQUEST / EXEC_TIME |
| PHP 8.4 implicit nullable usage | ✅ none |
| Services.yaml autowire + autoconfigure | ✅ |
| PSR-14 listeners (via `AsEventListener` or tag) | ✅ two listeners in `Classes/Listener/` |
| Extbase controllers use constructor DI | ✅ |
| 85 × `GeneralUtility::makeInstance` in Classes/ | ⚠️ see finding #2 |
| Default Fluid templates use Bootstrap 4 data attrs | ⚠️ see finding #1 |
| CSP config (`Configuration/ContentSecurityPolicies.php`) | ⚠️ absent — see finding #3 |
| XLIFF hygiene | ✅ 7 catalogue files, no empty `<source>` / `<target>` |
| `ext_emconf.php` starts with `declare(strict_types=1)` | ⚠️ see finding #4 |

## Findings (priority-ordered)

### 1. Default Fluid layouts still use Bootstrap 4 data-attributes (HIGH for TER)

`Resources/Private/Layouts/Page/Default.html:11`,
`Resources/Private/Templates/Layouts/Pages/Default.fluid.html:15` and
`Resources/Private/Templates/Pages/Default/default.fluid.html:15` emit:

```html
<button class="navbar-toggler" type="button"
        data-toggle="collapse" data-target="#navbarNav" …>
```

Bootstrap 5 renamed these to `data-bs-toggle` / `data-bs-target`. The
`ModernBootstrap` variants (`Resources/Private/Templates/ModernBootstrap/
Layouts/Page/Default.html:11` and its siblings) are correct — so the
pattern is known; the legacy defaults were simply not migrated with the
rest.

**Fix:** Port the three default files to `data-bs-*`. No behaviour
change on Bootstrap 5 sites, and fixes the toggler silently breaking on
BS5-only installs.

### 2. 85 × `GeneralUtility::makeInstance()` inside `Classes/` (MEDIUM)

Distribution (from grep):

- ~30 × in ViewHelpers requesting `UriBuilder` / `ConfigurationManagerInterface` — ViewHelpers are instantiated by Fluid itself and cannot have constructor DI in their rendering signature; these are legitimate.
- ~15 × in `Classes/Updates/*Update.php` for upgrade-wizard helpers (`SlugHelper`, `ConnectionPool`). Upgrade wizards run outside of DI and are canonical use.
- ~10 × in `Classes/Notification/**` for mail + notification lookup — those should flow through the future tagged-iterator refactor (see upgrade audit #4).
- ~10 × in repositories' `initializeObject()` pulling `ConfigurationManagerInterface`, `Context`, `Typo3QuerySettings` — Extbase Repository initialization happens before DI can inject, so this is the canonical workaround.
- The rest are in `Backend/View/BlogPostHeaderContentRenderer`, `AvatarProvider/*`, `Hooks/DataHandlerHook` (fallbacks in a hook whose class name is registered via SC_OPTIONS, so DI cannot reach it).

All 85 sites are either unavoidable by the current TYPO3 API surface or
already tracked as future refactors. No action this cycle.

### 3. No `Configuration/ContentSecurityPolicies.php` (MEDIUM)

TYPO3 v13 introduced the default backend CSP. v14 shipped a more strict
default. Because the blog backend module loads inline CSS via
`pageRenderer->addCssFile('EXT:blog/Resources/Public/Css/backend.min.css', …)`
and a JavaScriptModule importmap at `@t3g/blog/`, both of which are
already compatible with `'self'`-only policies, the extension works
**without** its own CSP policy override on v14. However, nothing is
enforced in tests, so a future developer adding an inline `<script>` or
external `<img>` source would break CSP silently.

**Fix (optional, scoped):** Add a minimal
`Configuration/ContentSecurityPolicies.php` asserting `'self'` for the
blog module scope so regressions show up as CSP violations in the
browser console. Kept as optional for this cycle.

### 4. `ext_emconf.php` has `declare(strict_types=1)` (LOW)

`ext_emconf.php:3` declares strict types. The TER build pipeline and
most install-tool parsers read `ext_emconf.php` as a data file with a
simple `$EM_CONF[$_EXTKEY] = [...]` statement. Strict types is harmless
at runtime (nothing in the file triggers coercion), but the TYPO3
coding guidelines explicitly say "not strict_types in ext_emconf.php"
so parsers that strip the header for validation do not choke.

**Fix:** Remove the `declare` line (and the blank line after it) from
`ext_emconf.php`. Also reflect this in the php-cs-fixer rule: currently
it enforces strict_types on ext_emconf.php via the `append()`
configuration. Either remove the file from the appended list, or add an
exclusion.

### 5. No Rector / Fractor / upgrade-tool configuration (INFO)

As noted in the extension-upgrade audit — not required while on
v14-only, but useful when v15 lands.

## Planned changes (this cycle)

1. Migrate the 3 default Fluid files to `data-bs-*` (finding #1).
2. Drop `declare(strict_types=1)` from `ext_emconf.php` and teach
   php-cs-fixer to exclude it (finding #4).

Deferred:

- CSP policy file (finding #3) — add in the security skill cycle.
- CSP regression test.
- Notification registry → tagged iterator (tracked in upgrade audit #4).
