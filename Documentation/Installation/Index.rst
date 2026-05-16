.. include:: ../Includes.txt

.. _Installation:

============
Installation
============

Requirements
============

.. list-table::
   :header-rows: 1
   :widths: 25 25 15

   * - Blog Extension
     - TYPO3
     - PHP
   * - 14.x
     - 14.2-14.x
     - 8.2-8.4

Installation via Composer
=========================

.. code-block:: bash

   composer require t3g/blog

This is the recommended installation method for TYPO3 v14.

Local Development Setup
=======================

The extension repository does not ship a tracked DDEV setup. Use any local
PHP 8.2-8.4 and MySQL or MariaDB environment that can run Composer and
Node.js/npm.

Typical project bootstrap:

.. code-block:: bash

   composer update
   npm ci
   npm run build

See :ref:`Development <Development>` for the full local testing workflow.


Activation
==========

.. rst-class:: bignums

1. Add a Blog site set

   Go to your site configuration and add one of the public Blog site sets:
   ``blog/standalone``, ``blog/integration`` or ``blog/bootstrap-53``.

2. Configure settings

   Adjust the blog settings in your site configuration. At minimum,
   set the page IDs for the blog root, data folder, and list pages.

See :ref:`Setup <Setup>` for detailed instructions.
See :ref:`ConfigurationSiteSets` for the site set overview.
