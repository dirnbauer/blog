.. include:: ../Includes.txt

.. _Setup:

=====
Setup
=====

.. toctree::
   :titlesonly:

   Wizard/Index
   Manual/Index

Setup Wizard
============

The Setup Wizard creates a fully configured **standalone** instance of the TYPO3
Blog Extension. If you already have an existing site, you might dislike the result
of having an additional and unplanned root page. In that case, please read the
manual setup instructions.

The wizard creates the blog page tree, adds the ``blog/standalone`` site set,
imports ``EXT:blog/Configuration/Routes/Default.yaml`` and writes the required
page ID settings.

:ref:`Setup Wizard <SetupWizard>`

Manual Setup
============

The manual setup helps you to build an **integrated** instance of the TYPO3 Blog
Extension. If you want a standalone Blog and do not have an existing
site, please go with the Setup Wizard instructions.

Manual setups must add a Blog site set, create the required pages and data
folder, write the page ID settings and include the route enhancer import.
See :ref:`Configuration` for the configuration reference.

:ref:`Manual Setup <SetupManual>`

.. note::

   The extension setup is independent of your local development stack. The
   repository no longer includes a tracked DDEV configuration; use any local
   TYPO3 environment that satisfies the installation requirements.
