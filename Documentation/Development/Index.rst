.. include:: ../Includes.txt

.. _Development:

===========
Development
===========

The repository targets TYPO3 v14 and does not ship a tracked DDEV setup.
Use any local PHP/MySQL environment that can run Composer and Node.js.

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

Functional Tests
================

The functional test runner assumes a local MySQL or MariaDB instance on
``127.0.0.1:3306`` with database ``t3func`` and credentials ``root`` / ``root``
unless you override the environment variables.

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
- a frontend build job that verifies committed assets are up to date

Tagged releases use Tailor to publish the extension to TER.
