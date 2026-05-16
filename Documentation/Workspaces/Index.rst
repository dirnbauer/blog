
.. include:: ../Includes.txt

.. _Workspaces:

=========================
Workspace Support
=========================

The TYPO3 Blog Extension supports TYPO3 Workspaces for staging and
reviewing blog content before publishing to the live site.

.. contents:: Table of Contents
   :local:
   :depth: 2

Supported Tables
================

The following tables support workspace versioning:

.. list-table:: Workspace-Enabled Tables
   :header-rows: 1
   :widths: 30 20 50

   * - Table
     - Versioning Mode
     - Description
   * - ``pages`` (blog posts)
     - ``versioningWS``
     - Blog posts (doktype 137) and blog pages (doktype 138) are
       fully workspace-enabled through TYPO3 core.
   * - ``tx_blog_domain_model_tag``
     - ``versioningWS``
     - Tags can be created, modified, and staged in workspaces
       before publishing.
   * - ``tx_blog_domain_model_author``
     - ``versioningWS``
     - Author records can be staged in workspaces.
   * - ``tx_blog_domain_model_comment``
     - ``alwaysAllowLiveEdit``
     - Comments are always live-editable, even in workspace context.
       They are visitor-generated content and do not go through
       editorial staging.
   * - ``sys_category``
     - ``versioningWS``
     - Categories are workspace-enabled through TYPO3 core.
   * - ``tt_content``
     - ``versioningWS``
     - Content elements are workspace-enabled through TYPO3 core.

Editor Workflow
===============

Creating Blog Content in Workspaces
------------------------------------

1. Switch to a workspace in the TYPO3 backend.
2. Create or edit blog posts as usual. Changes are staged in the
   workspace and not visible on the live site.
3. Create or modify tags and authors — these are also staged.
4. Review the workspace preview to verify changes.
5. Publish the workspace to make all changes live.

Comments During Workspace Editing
----------------------------------

Comments submitted by visitors are always written to the live database,
regardless of which workspace is active. This ensures:

- Visitors can always comment on published posts.
- Comment moderation works independently of workspace state.
- Approving or declining comments takes effect immediately.

Backend Modules
===============

The blog backend modules are available in all workspaces:

- **Posts module** — Available in all workspaces (``workspaces: '*'``).
  Shows posts with workspace overlay applied automatically.
- **Comments module** — Available in all workspaces. Comment status
  changes are applied live.
- **Setup module** — Available in live workspace only
  (``workspaces: 'live'``). Blog setup should not be modified in
  workspace context.

DataHandler Integration
=======================

The blog extension hooks into TYPO3's DataHandler to maintain derived
fields (``publish_date``, ``crdate_month``, ``crdate_year``) on blog
post pages. In workspace context:

- Workspace placeholder records are skipped to avoid corrupting
  structural records. The TYPO3 v14 valid non-live states are
  ``t3ver_state = 1`` (workspace-new), ``2`` (delete placeholder) and
  ``4`` (move pointer). States ``-1`` and ``3`` from pre-v11 releases
  were removed together with ``t3ver_move_id``.
- Cache is only flushed when operating in the live workspace. Workspace
  saves do not trigger cache invalidation since workspace content is
  not visible on the live site.

Version Compatibility
=====================

.. list-table::
   :header-rows: 1
   :widths: 20 20 20

   * - Blog Extension
     - TYPO3
     - PHP
   * - 14.x
     - 14.3-14.x
     - 8.2-8.4

The extension targets TYPO3 v14 only.
