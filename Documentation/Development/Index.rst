.. include:: ../Includes.txt

.. _Development:

===========
Development
===========

The repository targets TYPO3 v14.2 and later and does not ship a tracked
DDEV setup. Use any local PHP 8.2-8.4 and MySQL or MariaDB environment
that can run Composer and Node.js/npm.

Local Setup
===========

Install PHP dependencies and the frontend toolchain:

.. code-block:: bash

   composer update
   npm ci

Build the frontend assets:

.. code-block:: bash

   npm run build

PHP Checks
==========

The extension exposes the main PHP checks through Composer scripts:

.. code-block:: bash

   composer test:php:lint
   composer test:php:unit
   composer test:php:functional
   composer phpstan
   composer cgl
   composer cgl:fix

The dispatcher in ``Build/Scripts/runTests.sh`` mirrors the Composer
scripts and can run individual suites:

.. code-block:: bash

   Build/Scripts/runTests.sh -s lint
   Build/Scripts/runTests.sh -s cgl
   Build/Scripts/runTests.sh -s phpstan
   Build/Scripts/runTests.sh -s unit
   Build/Scripts/runTests.sh -s functional
   Build/Scripts/runTests.sh -s all

Functional Tests
================

The functional test runner assumes a local MySQL or MariaDB instance on
``127.0.0.1:3306`` with database ``t3func`` and credentials ``root`` / ``root``
unless you override the environment variables.
It also defaults ``TYPO3_PATH_APP`` to ``.build`` and ``TYPO3_PATH_ROOT``
to ``.build/public``.

Typical overrides:

.. code-block:: bash

   export typo3DatabaseDriver=mysqli
   export typo3DatabaseHost=127.0.0.1
   export typo3DatabasePort=3306
   export typo3DatabaseName=t3func
   export typo3DatabaseUsername=root
   export typo3DatabasePassword=root

   composer test:php:functional

Browser Smoke Tests
===================

Playwright smoke tests cover the rendered blog list and blog post pages in a
real browser. They target any running TYPO3 instance and skip automatically if
``BLOG_BASE_URL`` is not set.

.. code-block:: bash

   export BLOG_BASE_URL=https://example.test
   export BLOG_LIST_PATH=/blog1/
   export BLOG_POST_PATH=/blog1/first-blog-post

   npm run playwright:install
   npm run test:e2e

Continuous Integration
======================

GitHub Actions runs:

- PHP linting, coding standards, PHPStan, unit tests, and functional tests
  against PHP 8.2, 8.3, and 8.4
- a frontend build job on Node.js 22 that verifies committed assets are up
  to date
- an opt-in Playwright job that only runs when the ``BLOG_BASE_URL``
  repository secret is set (and emits a clear warning otherwise)

Tagged releases use Tailor to publish the extension to TER.

.. _template-migration-notes:

Template migration notes
========================

Sitepackages that override the shipped blog page templates need to align
their ``renderPlugin`` section with the current rendering pipeline:

.. code-block:: html

   <f:section name="renderPlugin">
       <f:cObject typoscriptObjectPath="tt_content.{listType}.20" />
   </f:section>

Why this changed
----------------

TYPO3 v14 introduced the ``record-transformation`` data processor on
``lib.contentElement``. The processor requires every ``tt_content`` row to
carry the full set of system fields (``sys_language_uid``, ``l18n_parent``,
``t3ver_wsid``, ``header`` and the rest). The pre-v14 pattern rendered
synthetic ``tt_content`` rows that lacked those fields, which surfaced as
``IncompleteRecordException`` during rendering.

Rendering the ``EXTBASEPLUGIN`` content object directly (``tt_content.{listType}.20``)
bypasses the content-element pipeline and keeps the output
workspace-safe.

Affected files
--------------

All files under the following paths ship the new pattern out of the box:

- ``Resources/Private/Templates/Pages/*.fluid.html``
  (the TYPO3 v14 ``PAGEVIEW`` templates — recommended)
- ``Resources/Private/Templates/Page/*.html``
  (the legacy integration templates kept for backwards compatibility)
- ``Resources/Private/Templates/Layouts/Pages/Default.fluid.html``
- The ``Resources/Private/Templates/Bootstrap53/`` template variant

If your sitepackage overrides any of those files, update the
``renderPlugin`` section to the form above. No other template changes
are required.

Removal
-------

The legacy synthetic ``tt_content`` rendering pattern is no longer part
of the supported rendering path. Overrides that still render synthetic
rows must be migrated to ``tt_content.{listType}.20``.
