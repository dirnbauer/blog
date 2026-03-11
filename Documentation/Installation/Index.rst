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
   * - 14.0.x
     - 13.4 LTS, 14.x
     - >= 8.1

Installation via Composer
=========================

.. code-block:: bash

   composer require t3g/blog

This is the recommended installation method for TYPO3 v13 and v14.


Activation
==========

.. rst-class:: bignums

1. Add the Blog site set

   Go to your site configuration and add the Blog site set. Choose
   either **Standalone** (creates its own page tree) or **Integration**
   (integrates into your existing site).

2. Configure settings

   Adjust the blog settings in your site configuration. At minimum,
   set the page IDs for the blog root, data folder, and list pages.

See :ref:`Setup <Setup>` for detailed instructions.
