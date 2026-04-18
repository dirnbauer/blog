# Documentation Audit

_Date: 2026-04-18_
_Skill: typo3-docs_

## Scope

Covered: `README.md`, `README.rst`, `Documentation/*` RST tree,
`guides.xml`, `CHANGELOG.md`.

## Findings

### 1. `Documentation/.editorconfig` missing (LOW)

The skill mandates a `.editorconfig` inside `Documentation/` to lock
the docs renderer to the TYPO3 norms (4-space indent, 80 char line,
LF endings, UTF-8). No file today.

**Fix (this cycle):** add the standard TYPO3 `.editorconfig` snippet
for `*.rst` / `*.xml`.

### 2. `README.md` ↔ `README.rst` are out of sync (LOW)

Both files exist. TYPO3 documentation convention is to ship **either**
a `README.rst` (if Documentation/ renders the RST) **or** a
`README.md` (for GitHub) — not both. Today the two files describe the
same project but the `.md` is richer (breaking-change note, full
feature list, test instructions) and the `.rst` is a trimmed-down
version.

**Fix (this cycle):** keep `README.md` as the single canonical landing
page. Replace `README.rst` with a thin redirect pointing to the
Markdown file so Packagist / TER tooling that reads the `.rst` still
resolves something sensible.

### 3. No `CHANGELOG` / `ReleaseNotes` entry for the recent security
work (MEDIUM)

`CHANGELOG.md` has an `# Unreleased` section but only lists
house-keeping tasks. The five landing commits from today (admin guard,
honeypot, captcha validator hardening, Bootstrap 5 template migration,
SRI, legacy TypoScript removal, PHPStan ruleset addition) are not
listed.

**Fix (this cycle):** append the `[FEATURE]` / `[TASK]` / `[SECURITY]`
lines to `# Unreleased` so the next release tag carries an accurate
changelog.

### 4. Workspaces doc references the wrong valid-state list (LOW)

`Documentation/Workspaces/Index.rst:95-99` says "workspace placeholder
records (new, delete, move placeholders) are skipped" but does not
enumerate the `t3ver_state` values. Now that the DataHandler fix
corrected the valid states from `[1, 2, 3]` to `[1, 2, 4]`, the
documentation could spell them out to help integrators who extend the
hook.

**Fix (optional, small):** add a sentence listing the states.

### 5. `Documentation/Reviews/` directory is new (INFO)

The five audit reports generated during this review cycle live in
`Documentation/Reviews/*.md`. They should be linked from the
`Documentation/Index.rst` toctree so the rendered docs surface them,
or explicitly excluded via `guides.xml` (they are Markdown, not RST,
so the TYPO3 docs renderer will skip them already).

**Decision:** leave unlinked. The reports are internal audit
artefacts, not end-user documentation. The `.md` extension keeps them
out of the RST toctree by default, which matches intent.

### 6. `guides.xml` version mismatch (OK)

`guides.xml` pins `version="14.1.0"` which matches `ext_emconf.php` and
`composer.json`. No action.

### 7. Core metadata links (OK)

`README.md` badges reference TYPO3 v14, PHP ≥ 8.2, GPL-2.0. All
correct. The composer `require` snippet uses `t3g/blog` which matches
`composer.json`.

### 8. "Breaking Changes for TYPO3 v14" section in README.md is pre-14
internal-upgrade commentary (LOW)

`README.md:11-45` describes the `renderPlugin` change that landed
during the v14 preparation. That is historical context — valuable once,
clutter on an extension already shipping v14-only. Most readers hitting
the README today want quickstart, not the migration rationale.

**Fix (this cycle):** move the breaking-change text into
`Documentation/Development/Index.rst` as a "Template migration notes"
subsection. The README keeps a single-line pointer.

## Planned changes (this cycle)

1. Add `Documentation/.editorconfig`.
2. Replace `README.rst` with a 6-line pointer to `README.md`.
3. Backfill `CHANGELOG.md` `# Unreleased` with the security work.
4. Slim the `## ⚠️ Breaking Changes for TYPO3 v14` section in
   `README.md` to a short pointer; move the body into the Development
   guide.
5. Add a one-line note about the valid `t3ver_state` values in
   `Documentation/Workspaces/Index.rst`.
