# Workspace Readiness Report

**Extension:** t3g/blog v14.1.0  
**Date:** 2026-03-11
**Scope:** TYPO3 Workspaces compatibility audit

## 1. TCA Audit — Custom Tables

| Table | `versioningWS` | Status | Action Required |
|-------|---------------|--------|-----------------|
| `tx_blog_domain_model_tag` | Not set | FAIL | Add `'versioningWS' => true` |
| `tx_blog_domain_model_author` | Not set | FAIL | Add `'versioningWS' => true` |
| `tx_blog_domain_model_comment` | Not set | FAIL | Add `'versioningWS_alwaysAllowLiveEdit' => true` (comments are live user interactions) |
| `pages` (core) | `true` (default) | OK | No action |
| `sys_category` (core) | `true` (default) | OK | No action |
| `tt_content` (core) | `true` (default) | OK | No action |

### Tag table — inline IRRE children

`tx_blog_domain_model_tag.content` uses `type=inline` with `foreign_table=tt_content`. In TYPO3 v14, all inline child tables of workspace-enabled parents **must** have `versioningWS => true`. Since `tt_content` already has this, the IRRE relation is safe once the tag table itself has `versioningWS => true`.

### Comment table rationale

Comments are created by frontend visitors in real time. They are not editorial content that goes through a staging workflow. Setting `versioningWS_alwaysAllowLiveEdit => true` ensures comments remain editable live even when a workspace is active, which is the correct behavior.

## 2. Backend Module Audit

### `BackendController` (Classes/Controller/BackendController.php)

The backend controller uses **Extbase repositories** for all queries:
- `postsAction()` — uses `PostRepository::findAllByPid()` (Extbase query)
- `commentsAction()` — uses `CommentRepository::findAllByFilter()` (Extbase query)
- `updateCommentStatusAction()` — uses `CommentRepository::findByUid()` (Extbase)

**Status:** OK — Extbase handles workspace overlay automatically through its persistence layer. No manual `BackendUtility::workspaceOL()` calls are needed.

### `Modules.php` (Configuration/Backend/Modules.php)

No `'workspaces'` key is set on any module. By default, backend modules without this key are available in all workspaces (`'*'`), so this is implicitly correct. However, explicitly setting `'workspaces' => '*'` is recommended for clarity.

**Action required:** Add `'workspaces' => '*'` to each module registration.

## 3. DataHandler Hook Audit

### `DataHandlerHook` (Classes/Hooks/DataHandlerHook.php)

**Issue 1 — Workspace record processing:**
The hook runs `processDatamap_afterDatabaseOperations` on ALL pages records, including workspace versions. When a workspace version is saved:
- The `$id` parameter is the UID of the workspace version record
- The hook queries `publish_date` for that UID and updates `crdate_month`/`crdate_year`
- This is **acceptable** because workspace versions should have correct derived fields

However, the hook should skip **new placeholders** (t3ver_state=1) and **delete placeholders** (t3ver_state=2) since these are structural records, not content records.

**Issue 2 — Cache flushing on workspace saves:**
The hook flushes page cache on every DataHandler operation, including workspace saves. Cache should only be flushed for live workspace operations. Flushing cache on workspace saves is wasteful since workspace content is not visible on the live site.

**Action required:**
- Skip processing for workspace placeholder records (t3ver_state = 1, 2, 3)
- Only flush cache when operating in the live workspace (wsid = 0) or on publish

## 4. Repository Audit

### PostRepository (Classes/Domain/Repository/PostRepository.php)

All queries use Extbase Query API — workspace overlay handled automatically.

**Status:** OK

### CommentRepository (Classes/Domain/Repository/CommentRepository.php)

**Issue:** `getPostPidsByRootPid()` (line 116-133) uses direct QueryBuilder without workspace restrictions:

```php
$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
    ->getQueryBuilderForTable('pages');
$rows = $queryBuilder
    ->select('uid')
    ->from('pages')
    ->where($queryBuilder->expr()->eq('doktype', Constants::DOKTYPE_BLOG_POST))
    ->andWhere($queryBuilder->expr()->eq('pid', $blogRootPid))
    ->executeQuery()
    ->fetchAllAssociative();
```

This query uses the default `DefaultRestrictionContainer` which does NOT include `WorkspaceRestriction`. In a backend workspace context, this may return workspace placeholder records or miss workspace-only records.

**Action required:** This method is only called from backend context (BackendController). The query is used to find post page UIDs under a blog root — it should work correctly because live page UIDs are the canonical identifiers. No change needed since we only need the live page UIDs as PID references for comments.

### TagRepository (Classes/Domain/Repository/TagRepository.php)

**Issue:** `findTopByUsage()` (line 44-77) uses direct QueryBuilder with a **raw SQL injection vulnerability**:

```php
$queryBuilder->where('t.pid IN(' . implode(',', $storagePids) . ')');
```

While `$storagePids` is generated from `GeneralUtility::intExplode()` (which casts to int), this pattern bypasses QueryBuilder's prepared statement protection and should use `createNamedParameter()`.

Additionally, this query has no workspace awareness. The MM table join does not account for workspace versions of tag records.

**Action required:**
- Fix SQL injection pattern — use `$queryBuilder->expr()->in()` with proper parameters
- For workspace awareness: since this is a frontend query counting tag usage, the default restrictions should suffice after fixing the query builder usage

### AuthorRepository (Classes/Domain/Repository/AuthorRepository.php)

Pure Extbase — no custom QueryBuilder.

**Status:** OK

### CategoryRepository (Classes/Domain/Repository/CategoryRepository.php)

`getByReference()` uses QueryBuilder on `sys_category_record_mm` then Extbase for the actual records. MM table queries don't need workspace overlay since MM relations are handled through parent record overlay by DataHandler.

**Status:** OK

## 5. CacheService Audit

### CacheService (Classes/Service/CacheService.php)

Uses `CacheTag` objects and `CacheManager` for cache operations. No workspace-specific concerns.

**Status:** OK — Cache tags use record UIDs which are consistent across workspaces.

## 6. ext_localconf.php Audit

Two DataHandler hooks are registered via `SC_OPTIONS`:
- `DataHandlerHook` — workspace concerns documented above
- `CreateSiteConfigurationHook` — creates site configuration for new blog pages. Should skip workspace records.

**Action required:** Verify `CreateSiteConfigurationHook` does not create site configurations for workspace placeholder records.

## 7. Summary of Required Changes

| Priority | Component | Change |
|----------|-----------|--------|
| HIGH | TCA: tag | Add `versioningWS => true` |
| HIGH | TCA: author | Add `versioningWS => true` |
| HIGH | TCA: comment | Add `versioningWS_alwaysAllowLiveEdit => true` |
| MEDIUM | DataHandlerHook | Skip workspace placeholders, conditional cache flush |
| MEDIUM | TagRepository | Fix SQL injection pattern in `findTopByUsage()` |
| LOW | Modules.php | Add explicit `workspaces => '*'` |
| LOW | CreateSiteConfigurationHook | Verify workspace safety |

## 8. Risk Assessment

- **Low risk:** Enabling `versioningWS` on tag/author tables. These are standard editorial content that benefits from workspace staging.
- **Low risk:** Comment `alwaysAllowLiveEdit`. Preserves current behavior (comments are always live).
- **Medium risk:** DataHandlerHook changes. The `publish_date`/`crdate_month`/`crdate_year` logic must correctly handle workspace publish events.
- **No risk:** Backend module workspace availability. Already works implicitly.
