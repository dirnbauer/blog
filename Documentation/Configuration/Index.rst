.. include:: ../Includes.txt

.. _Configuration:

=============
Configuration
=============

This page summarizes the configuration entry points that are shipped by
the extension.

.. contents::
   :local:
   :depth: 2

.. _ConfigurationSiteSets:

Site sets
=========

Add one of the public Blog site sets to your site configuration:

.. list-table::
   :header-rows: 1
   :widths: 30 70

   * - Site set
     - Purpose
   * - ``blog/standalone``
     - Full standalone blog rendering based on TYPO3 ``PAGEVIEW``.
   * - ``blog/integration``
     - Integration into an existing site. It depends on ``blog/static``
       and ``blog/shared`` and switches blog pages to the shipped legacy
       integration templates.
   * - ``blog/bootstrap-53``
     - Bootstrap 5.3 template variant. It depends on ``blog/standalone``.

The internal ``blog/static`` and ``blog/shared`` sets are hidden helper
sets and should normally be pulled in through one of the public sets.

.. _ConfigurationRequiredPageIds:

Required page IDs
=================

Manual setups must configure the page IDs used by links, storage and
list plugins:

.. code-block:: yaml
   :caption: config/sites/<identifier>/settings.yaml

   plugin:
      tx_blog:
         settings:
            blogUid: 12
            categoryUid: 13
            tagUid: 14
            authorUid: 15
            archiveUid: 16
            storagePid: 17

The setup wizard writes these settings automatically for generated
standalone blogs.

.. _ConfigurationRouting:

Routing
========

For clean frontend URLs, import the default route enhancer collection in
your site configuration:

.. code-block:: yaml
   :caption: config/sites/<identifier>/config.yaml

   imports:
      - { resource: "EXT:blog/Configuration/Routes/Default.yaml" }

The import includes routes for categories, tags, archive pages, author
lists, feed URLs and list pagination.

.. _ConfigurationComments:

Comments
========

Comments are enabled by default. The shipped settings include:

.. list-table::
   :header-rows: 1
   :widths: 55 20 25

   * - Setting
     - Default
     - Purpose
   * - ``plugin.tx_blog.settings.comments.active``
     - ``true``
     - Enables local comments globally.
   * - ``plugin.tx_blog.settings.comments.features.urls``
     - ``true``
     - Enables the URL field in the local comment form.
   * - ``plugin.tx_blog.settings.comments.moderation``
     - ``0``
     - ``0`` approves new comments directly. ``1`` and ``2`` store new
       comments as pending.
   * - ``plugin.tx_blog.settings.comments.disqus.enable``
     - ``false``
     - Enables Disqus rendering instead of local comments.
   * - ``plugin.tx_blog.settings.comments.google_recaptcha.enable``
     - ``false``
     - Enables Google reCAPTCHA validation for local comments.

Notification emails are enabled by default for authors and administrators.
Set ``plugin.tx_blog.settings.notifications.email.senderMail`` and
``plugin.tx_blog.settings.notifications.CommentAddedNotification.admin.email``
to site-specific addresses before using notifications in production.

.. _ConfigurationAvatars:

Avatars
========

Authors can use either the built-in Gravatar provider or the uploaded
image provider. The Gravatar URL builder uses the following defaults:

.. list-table::
   :header-rows: 1
   :widths: 55 20 25

   * - Setting
     - Default
     - Purpose
   * - ``plugin.tx_blog.settings.authors.avatar.provider.size``
     - ``72``
     - Requested avatar size in pixels.
   * - ``plugin.tx_blog.settings.authors.avatar.provider.default``
     - ``mm``
     - Gravatar fallback image code.
   * - ``plugin.tx_blog.settings.authors.avatar.provider.rating``
     - ``g``
     - Maximum Gravatar rating.

The extension-level ``enableGravatarProxy`` option is disabled by
default. When enabled, TYPO3 downloads Gravatar images and serves them
from ``typo3temp/assets/t3g/blog/gravatar/``.

.. _ConfigurationAssets:

Frontend assets and CSP
=======================

The static set includes ``Resources/Public/Css/frontend.min.css``.

The ``blog/standalone`` set additionally loads Bootstrap 5.3 from
jsDelivr with Subresource Integrity attributes. Sites with strict
Content Security Policy headers should either self-host those assets or
allow ``cdn.jsdelivr.net`` for the required style and script sources.

.. _ConfigurationExtension:

Extension configuration
=======================

The extension exposes two extension configuration options:

.. list-table::
   :header-rows: 1
   :widths: 35 20 45

   * - Option
     - Default
     - Purpose
   * - ``disablePageLayoutHeader``
     - ``0``
     - Disables the blog information bar in the page and list modules.
   * - ``enableGravatarProxy``
     - ``0``
     - Fetches Gravatar images server-side and stores them below
       ``typo3temp``.
