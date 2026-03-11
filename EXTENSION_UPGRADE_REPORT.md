# Extension Upgrade Report — Recheck

**Extension:** t3g/blog v14.0.0  
**Date:** 2026-03-11  
**Scope:** TYPO3 v13/v14 upgrade readiness with focus on active deprecations

## Recheck Evidence

- `ddev composer phpstan` run against current branch
- current `composer.json` supports TYPO3 13.4 and 14.x
- current code already migrated from removed `$GLOBALS['TSFE']` usage

## Current Findings

### High — Install namespace upgrade APIs still used

The update wizards still use deprecated install classes and attributes:

- `TYPO3\CMS\Install\Attribute\UpgradeWizard`
- `TYPO3\CMS\Install\Updates\UpgradeWizardInterface`
- `TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite`
- `TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate`

This affects all upgrade wizard classes in `Classes/Updates`.

**Target migration:** switch to `TYPO3\CMS\Core\Attribute\UpgradeWizard` and
`TYPO3\CMS\Core\Upgrades\*`.

### High — Removed mail API call in TYPO3 v14

`Classes/Mail/MailMessage.php` still calls `CoreMailMessage::send()`, which is
removed in TYPO3 v14.

**Target migration:** send via `TYPO3\CMS\Core\Mail\MailerInterface`.

### Medium — Deprecated FlexForm registration helper

`Configuration/TCA/Overrides/tt_content.php` uses deprecated
`ExtensionManagementUtility::addPiFlexFormValue()`.

**Target migration:** register the DS directly in TCA
(`columnsOverrides.pi_flexform.config.ds`).

## Already Resolved Since Previous Pass

- `BlogVariableProvider` now uses request attributes instead of `$GLOBALS['TSFE']`
- ext_emconf TYPO3 constraints already include v14
- relevant TCA columns already carry `searchable` flags
- obsolete `canNotCollapse` usage is already removed

## Planned Change Set

1. Migrate all update wizard imports from `Install` to `Core\Upgrades`
2. Replace removed `MailMessage::send()` usage with `MailerInterface::send()`
3. Replace deprecated FlexForm helper call with direct TCA DS registration
