# Extension Upgrade Audit — v14 Migration Debt

_Date: 2026-04-18_
_Skill: typo3-extension-upgrade_

## Target state

- `composer.json`: TYPO3 core `^14.0`, PHP `^8.2`
- `ext_emconf.php`: typo3 `14.0.0-14.99.99`, php `8.2.0-8.4.99`,
  version `14.1.0`
- `branch-alias`: `dev-master` → `14.1.x-dev`

The extension is already targeting v14 exclusively — nothing to widen or
narrow. This audit focuses on remaining **dead migration debt**: files,
flags, or code paths that exist only to support older TYPO3 versions and
are no longer needed.

## Findings

### 1. Legacy `Configuration/TypoScript/` directory still on disk (LOW)

The extension ships site sets (`Configuration/Sets/{Integration, Shared,
Standalone, ModernBootstrap, ModernTailwind, Static}`) which are the v13+
mechanism for TypoScript delivery. The pre-Sets directory
`Configuration/TypoScript/{Shared,Standalone,Static}` is still present,
but:

- `Configuration/TypoScript/Shared/setup.typoscript` (1 line) only
  re-imports `EXT:blog/Configuration/Sets/Shared/setup.typoscript`
- `Configuration/TypoScript/Standalone/setup.typoscript` (4 lines)
  re-imports `fluid_styled_content` + the three blog Sets
- `Configuration/TypoScript/Standalone/constants.typoscript` (30 lines)
  duplicates configuration that the Sets already provide through
  `settings.definitions.yaml`

`ext_typoscript_setup.txt` at the repository root is a 5-line stub that
just imports `plugin.tx_blog.persistence.storagePid`. No `<INCLUDE_TYPOSCRIPT>`
or legacy `staticFileRelations` register the directories, so nothing
outside documentation actually reads the legacy `.typoscript` files any
more.

**Fix:** Drop the legacy `Configuration/TypoScript/` tree and the
`ext_typoscript_setup.txt` stub. Keep only the Sets. No behaviour change
for v14 installs.

### 2. `ext_localconf.php` still uses `ExtensionUtility::configurePlugin()` per plugin (OK — canonical v14)

14 `configurePlugin()` calls in `ext_localconf.php` register the plugin
namespaces. Core still exposes this API on v14; the Content Blocks-style
per-plugin registration is an alternative, not a replacement. No
change needed, but worth a note for future refactor if the extension
migrates to Content Blocks.

### 3. SC_OPTIONS hook registrations (OK — no PSR-14 equivalent)

`ext_localconf.php:37-38,244-245` registers two DataHandler hooks via
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']
['processDatamapClass']`. The hook surface
`processDatamap_afterDatabaseOperations` has no PSR-14 equivalent in v14
(confirmed with the workspaces skill — v14 offers
`AfterRecordPublishedEvent` etc. which don't cover post-write
side-effects on every DataHandler run). This is the canonical API and
should stay.

### 4. `notificationRegistry` via `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']` (LOW — could be service-tagged)

`ext_localconf.php:252-255` registers notification processors in
`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Blog']['notificationRegistry']`.
`NotificationManager::notify()` iterates the array with
`GeneralUtility::makeInstance($className)` at runtime. This works but
bypasses DI — the registry should be a tagged iterable
(`#[AsTaggedItem]` or `tags: ['blog.notification.processor']` in
`Services.yaml`).

**Fix (optional, scoped):** Convert to tagged iterator. Touches
`NotificationManager`, both `Processor/*` classes, `Services.yaml`, and
removes the two lines from `ext_localconf.php`. Kept as "optional" since
the current code works and the refactor would break anyone extending
the registry in their own `ext_localconf.php`.

### 5. Routing aspect via `$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']` (OK)

`ext_localconf.php:248-249` registers `BlogStaticDatabaseMapper`. This
is still the documented v14 API for custom routing aspects — keep.

### 6. FormEngine FormDataProvider via `$GLOBALS` (OK)

`ext_localconf.php:31-34` registers `CategoryDefaultValueProvider`.
Again, the documented v14 API.

### 7. Fluid namespace registration via `$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']` (OK)

`ext_localconf.php:28` registers `blogvh`. Still v14 canonical.

### 8. `CategorySlugUpdate` / `TagSlugUpdate` reference `t3ver_oid` explicitly (OK)

`Classes/Updates/CategorySlugUpdate.php:51` and `TagSlugUpdate.php`
directly query `t3ver_oid`. The queries are correct for v14 workspace
data; no change.

### 9. `Post::getUri()` builds links via `LinkFactory + ContentObjectRenderer` (OK)

`Classes/Domain/Model/Post.php:365-372` uses `LinkFactory::create()` — the
modern v14 typolink API. No change.

### 10. No deprecated `ObjectManager`, `GeneralUtility::logDeprecatedFunction`, `EidUtility`, `GLOBALS['TYPO3_DB']` usage (OK)

Greps for the classic pre-v12 offenders return empty.

### 11. Rector / Fractor are not wired into the dev dependencies (LOW — informational)

The `composer.json` does not require `ssch/typo3-rector` or
`a9f/typo3-fractor`. That means future migrations to v15 will need to
add those tools first. Not an action item for the v14-only target, but
worth noting that the CI pipeline is PHPStan-only for TYPO3 API
compatibility checks.

### 12. Third-party deps on dev-only dependencies (OK)

`bk2k/bootstrap-package`, `bk2k/extension-helper`, `typo3/cms-*` are all
at `^14.0` or compatible. `typo3/testing-framework: ^9.0 || dev-main` is
the v14-aligned testing framework major.

## Planned changes

1. **Delete** `Configuration/TypoScript/` tree and
   `ext_typoscript_setup.txt` (findings #1). Keep
   `ext_typoscript_constants.txt` only if it contains anything — it is
   empty, so it also goes.
2. **Document** in the README that the legacy entry points have been
   removed and installations should depend on the Sets.
3. **Defer** the notificationRegistry → tagged-iterator refactor to its
   own cycle — it is dev-visible BC and should not slip into a cleanup
   commit.

No composer bumps, no TCA changes, no DB migrations.
