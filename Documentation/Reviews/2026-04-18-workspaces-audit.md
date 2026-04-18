# Workspaces Audit — TYPO3 v14

_Date: 2026-04-18_
_Skill: typo3-workspaces_

## Scope

Reviewed the blog extension for TYPO3 v14 workspace API compliance: TCA
`versioningWS` flags, backend and frontend overlay calls, DataHandler
hooks, and FAL file/collection limitations.

## Findings

### 1. `DataHandlerHook::isWorkspacePlaceholder` uses obsolete `t3ver_state` values (HIGH)

`Classes/Hooks/DataHandlerHook.php:108` checks whether a record is a
workspace placeholder via `in_array(..., [1, 2, 3], true)`. In TYPO3 v11+
`t3ver_state = 3` (old "move placeholder") was removed together with
`t3ver_state = -1` (old "new version" pendant) and the `t3ver_move_id`
field. On v14 the valid non-live values are **1** (workspace-new),
**2** (delete placeholder) and **4** (move pointer). Including `3` is
dead code, and omitting `4` means a workspace move operation will now
re-compute `publish_date` / `crdate_month` / `crdate_year` against the
move pointer row.

**Fix:** Replace `[1, 2, 3]` with `[1, 2, 4]`.

### 2. `tx_blog_domain_model_comment` is not workspace-versioned (MEDIUM — by design, should be explicit)

`Configuration/TCA/tx_blog_domain_model_comment.php:28` sets
`versioningWS_alwaysAllowLiveEdit: true` but never sets `versioningWS:
true`. In Core (`TcaSchemaFactory`/`DataMapFactory`) the
`versioningWS_alwaysAllowLiveEdit` flag is only honoured when the table
is workspace-enabled to begin with — without `versioningWS: true` the
flag is a no-op and the table is simply non-versioned.

That is **arguably correct by design** for comments (public user
submissions moderated via the backend, not staged editorially), but the
intent should be made explicit. Two options:

- **Keep non-versioned (recommended)** — remove
  `versioningWS_alwaysAllowLiveEdit` entirely since it has no effect.
- **Make it versioned + always-live-editable** — add `versioningWS:
  true`. This would keep the historical flag meaningful but currently
  no moderation workflow requires staging comments.

**Fix:** Remove the no-op flag. Document the reason inline.

### 3. `featured_image` references rely on sys_file_reference overlay (OK — but note FAL limitation)

`Configuration/TCA/Overrides/pages.php` registers `featured_image` as
`type: file`, which produces `sys_file_reference` rows. Those rows are
workspace-versioned by Core (`versioningWS: true` on `sys_file_reference`
is set in EXT:core's TCA), so references behave correctly on publish.

**However:** physical files under `fileadmin/` are NOT versioned. If an
editor replaces a featured image by overwriting the file, the new image
is immediately visible everywhere — including on live while the
workspace is still in review. This is a TYPO3-wide limitation documented
in the skill (section 2). No code fix; add an editor-facing note in the
docs.

### 4. Workspace-aware constraints in `PostRepository::initializeObject` (OK)

`Classes/Domain/Repository/PostRepository.php:77-89` already wires up
workspace-aware default constraints for backend requests:

```php
if ($workspaceId === 0) {
    $this->defaultConstraints[] = $query->equals('t3ver_wsid', 0);
} else {
    $this->defaultConstraints[] = $query->logicalOr(
        $query->equals('t3ver_wsid', 0),
        $query->equals('t3ver_wsid', $workspaceId),
    );
}
```

This is correct and matches the overlay semantics the skill describes.
No change needed.

### 5. `CommentRepository::getPostPidsByRootPids` bypasses workspace restrictions (LOW)

`Classes/Domain/Repository/CommentRepository.php:114-128` builds a raw
QueryBuilder against `pages` for the pid lookup and does **not** set a
`FrontendRestrictionContainer` or a `WorkspaceRestriction`. The method
only reads `uid` values for the `IN (...)` clause that follows; the
subsequent Extbase query (where the comments are actually fetched) is
workspace-aware. So functionally the outer lookup is safe — a workspace
version of a pid would still be covered because the live and workspace
pid share the same `uid`.

Still worth documenting as deliberate, or switch to the restriction
container for consistency with the rest of the codebase.

### 6. Backend listing routes use Extbase repositories (OK)

`BackendController::postsAction` / `commentsAction` call
`postRepository->findAllByPid(s)` / `commentRepository->findAllByFilter*`
which go through Extbase's `Typo3QuerySettings`. Extbase handles
workspace overlays transparently for standard finder methods; no manual
`BackendUtility::workspaceOL()` is required here.

### 7. No PSR-14 workspace event listeners (informational)

The extension does not currently react to
`AfterRecordPublishedEvent` / `SortVersionedDataEvent` etc. This is fine
— the blog has no custom caching or external integrations that would
need to react to publish. Listed only so future contributors know the
option exists.

### 8. `sys_file_collection` / folder-based collections (N/A)

The blog extension does not use `sys_file_collection`. The FAL-related
feature is `featured_image` (single image per post) and `media`
(`ObjectStorage<FileReference>` on `Post`). Both are static references,
not folder-based collections, so the "folder-based collection breaks
workspace isolation" pitfall from the skill does not apply.

## Planned changes

1. **DataHandlerHook**: fix `t3ver_state` placeholder list `[1,2,3]` → `[1,2,4]`.
2. **Comment TCA**: remove the no-op `versioningWS_alwaysAllowLiveEdit` flag with an inline reason.
3. **CommentRepository**: add `FrontendRestrictionContainer` to the pid lookup query for consistency.
4. **Documentation**: note the FAL/workspace limitation in the editor guide.

No TCA schema changes. No data migration needed. No effect on existing
workspace records. No public API break.
