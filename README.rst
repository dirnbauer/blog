.. image:: https://poser.pugx.org/t3g/blog/v/stable
   :alt: Latest Stable Version
   :target: https://extensions.typo3.org/extension/blog/

.. image:: https://img.shields.io/badge/TYPO3-14-orange.svg
   :alt: TYPO3 14
   :target: https://get.typo3.org/version/14

.. image:: https://poser.pugx.org/t3g/blog/d/total
   :alt: Total Downloads
   :target: https://packagist.org/packages/t3g/blog

.. image:: https://poser.pugx.org/t3g/blog/d/monthly
   :alt: Monthly Downloads
   :target: https://packagist.org/packages/t3g/blog

.. image:: https://github.com/TYPO3GmbH/blog/actions/workflows/ci.yml/badge.svg?branch=master
   :alt: Continuous Integration Status
   :target: https://github.com/TYPO3GmbH/blog/actions/workflows/ci.yml

=============================
TYPO3 CMS Extension  ``blog``
=============================

This blog extension uses TYPO3s core concepts and elements to provide a full-blown blog that users of TYPO3 can instantly understand and use.

.. list-table::

   * - Repository
     - https://github.com/TYPO3GmbH/blog

   * - Documentation
     - https://docs.typo3.org/p/t3g/blog/master/en-us/

   * - TER
     - https://extensions.typo3.org/extension/blog/

Installation
============

.. code-block:: bash

   composer require t3g/blog

For setup, development, and contribution details see the
`documentation <https://docs.typo3.org/p/t3g/blog/master/en-us/>`_ and the
`development guide <https://docs.typo3.org/p/t3g/blog/master/en-us/Development/Index.html>`_.

Compatibility
=============

.. list-table::
   :header-rows: 1

   * -
     - TYPO3
     - PHP

   * - 14.x
     - 14.x
     - >= 8.2

License
=======
GPL-2.0-or-later

Development
===========

The repository no longer ships a tracked DDEV setup. Use any local TYPO3 v14
environment with PHP 8.2+, MySQL or MariaDB, Composer, and Node.js.

Install PHP and frontend dependencies:

.. code-block:: bash

   composer update
   npm ci

Build frontend assets:

.. code-block:: bash

   npm run build

Run the PHP test suites:

.. code-block:: bash

   composer test:php:lint
   composer test:php:unit
   composer test:php:functional
   composer phpstan
   composer cgl
   composer cgl:fix

The functional runner defaults to a local MySQL or MariaDB instance on
``127.0.0.1:3306`` with database ``t3func`` and credentials ``root`` / ``root``.
Override the ``typo3Database*`` environment variables if your setup differs.

Browser smoke tests use Playwright and target any running TYPO3 site:

.. code-block:: bash

   export BLOG_BASE_URL=https://example.test
   export BLOG_LIST_PATH=/blog1/
   export BLOG_POST_PATH=/blog1/first-blog-post
   npm run playwright:install
   npm run test:e2e

Contribution
============

Any contributor is welcome to join our team. All you need is an github account.
If you already have an account, visit the: `github.com/TYPO3GmbH/blog <https://github.com/TYPO3GmbH/blog>`_.

It is also highly recommended to join our `Slack Channel: #t3g-ext-blog <https://typo3.slack.com/archives/t3g-ext-blog>`_
