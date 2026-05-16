.. include:: ../Includes.txt

.. _Extending:

=========
Extending
=========

.. contents::
   :local:
   :depth: 2

AvatarProvider
==============

The default AvatarProvider is the GravatarProvider, which fetches author
avatars from gravatar.com. The extension also provides an ImageProvider
for locally uploaded images.

To implement your own AvatarProvider:

1. Create a class that implements ``AvatarProviderInterface``.
2. Add your provider to the TCA field ``avatar_provider`` to make it
   selectable in the author record.

.. code-block:: php

   use T3G\AgencyPack\Blog\AvatarProvider\AvatarProviderInterface;
   use T3G\AgencyPack\Blog\Domain\Model\Author;

   class MyAvatarProvider implements AvatarProviderInterface
   {
       public function getAvatarUrl(Author $author, int $size): string
       {
           // Return the URL to the avatar image
       }
   }

.. note::

   Gravatar proxying is disabled by default. Enable the
   ``enableGravatarProxy`` extension configuration option if TYPO3 should
   download Gravatar images, store them below
   ``typo3temp/assets/t3g/blog/gravatar/`` and deliver the cached local
   file to visitors.


Comment Notifications
=====================

The extension dispatches notifications when new comments are added. Two
processors are included:

- ``AdminNotificationProcessor`` — sends an email to the site admin
- ``AuthorNotificationProcessor`` — sends an email to the post author(s)

Custom processors can be registered in ``ext_localconf.php``:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Blog']['notificationRegistry']
       [\T3G\AgencyPack\Blog\Notification\CommentAddedNotification::class][]
       = \Your\Extension\Notification\CustomProcessor::class;

Your processor must implement ``ProcessorInterface``. Processors receive
the current ``ServerRequestInterface`` and the notification object.


DataHandler Hooks
=================

The extension uses two DataHandler hooks:

- ``DataHandlerHook`` — maintains derived date fields
  (``crdate_month``, ``crdate_year``) from ``publish_date`` on blog
  posts and flushes page cache on record changes. Workspace placeholder
  records are skipped, and cache is only flushed in the live workspace.
- ``CreateSiteConfigurationHook`` — automatically creates a site
  configuration when a blog page (doktype 138) is created through TYPO3's
  site configuration hook.


Template Overrides
==================

All Fluid templates, partials, and layouts can be overridden in your
sitepackage. Set the template paths via TypoScript:

.. code-block:: typoscript

   plugin.tx_blog {
       view {
           templateRootPaths.10 = EXT:your_sitepackage/Resources/Private/Templates/Blog/
           partialRootPaths.10 = EXT:your_sitepackage/Resources/Private/Partials/Blog/
           layoutRootPaths.10 = EXT:your_sitepackage/Resources/Private/Layouts/Blog/
       }
   }
