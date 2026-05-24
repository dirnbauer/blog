.. include:: ../../Includes.txt

.. _SetupManual:

============
Manual Setup
============

The manual setup helps you to build an **integrated** instance of the TYPO3 Blog
Extension. If you want a standalone Blog and do not have an existing
site, please go with the :ref:`Setup Wizard <SetupWizard>` instructions.

Prerequisites:

1. Ensure the TYPO3 Blog Extension is :ref:`installed and activated <Installation>`
2. Ensure you are logged in as **Administrator**

.. rst-class:: bignums

   1. Edit your existing site configuration

      .. figure:: manual-1.png
         :alt: Open the site configuration for manual blog setup
         :zoom: lightbox
         :class: with-border with-shadow

   2. Add the "Blog: Integration" set to your site.

      .. figure:: manual-2.png
         :alt: Add the Blog Integration site set to an existing site
         :zoom: lightbox
         :class: with-border with-shadow

      .. tip::

         It is recommended to use the "Integration" template to use the prepared page
         templates for lists and posts. These are using the layout "Default" and the
         section "Main" of your Template.

         If your "Page-Template/-Sections" named differently, please overwrite the blog
         templates in your sitepackage. Check the used templates below and adapt them
         to your own needs.

         - `BlogList <https://github.com/TYPO3GmbH/blog/blob/master/Resources/Private/Templates/Page/BlogList.html>`__
         - `BlogPost <https://github.com/TYPO3GmbH/blog/blob/master/Resources/Private/Templates/Page/BlogPost.html>`__

      .. note::

         TYPO3 v14 standalone rendering uses the `PAGEVIEW` templates in
         `Resources/Private/Templates/Pages/*.fluid.html`. The links above refer to
         the legacy integration templates used by the "Blog: Integration" set.

   3. Create Blog Pages

      .. figure:: manual-3.png
         :alt: Create multiple pages below the selected page
         :zoom: lightbox
         :class: with-border with-shadow

      1. Right click the page you want the Blog to list below
      2. Select "More options"
      3. Select "Create multiple pages"

      .. figure:: manual-4.png
         :alt: Enter the blog pages and data folder for manual setup
         :zoom: lightbox
         :class: with-border with-shadow

      Create the following Pages, make sure they have the correct **Type**

      +-------------+-------------+
      | Name        | Type        |
      +=============+=============+
      | Blog        | Blog Page   |
      +-------------+-------------+
      | Authors     | Blog Page   |
      +-------------+-------------+
      | Categories  | Blog Page   |
      +-------------+-------------+
      | Tags        | Blog Page   |
      +-------------+-------------+
      | Archive     | Blog Page   |
      +-------------+-------------+
      | Data        | Folder      |
      +-------------+-------------+

      .. figure:: manual-5.png
         :alt: Configure the data folder to contain blog records
         :zoom: lightbox
         :class: with-border with-shadow

      Edit the newly generated Data folder to contain the "Blog"

      4. Edit the page "Data" and select the "Behaviour" tab
      5. Select "Blog" at "Contains Plugin"
      6. Save the Page

      .. figure:: manual-6.png
         :alt: Sort the manually created blog pages
         :zoom: lightbox
         :class: with-border with-shadow

      Sort the pages like the following screenshot

   4. Configure the page ids in the site settings

      .. code-block:: yaml
         :linenos:

         plugin:
            tx_blog:
               settings:
                  blogUid:       ID_of_Page_Blog
                  categoryUid:   ID_of_Page_Categories
                  tagUid:        ID_of_Page_Tags
                  authorUid:     ID_of_Page_Authors
                  archiveUid:    ID_of_Page_Archive
                  storagePid:    ID_of_Page_Data

      .. figure:: manual-7.png
         :alt: Open the site settings for blog page IDs
         :zoom: lightbox
         :class: with-border with-shadow

      .. figure:: manual-8.png
         :alt: Configure the blog page ID settings
         :zoom: lightbox
         :class: with-border with-shadow

      .. figure:: manual-9.png
         :alt: Save the blog page ID settings
         :zoom: lightbox
         :class: with-border with-shadow

      .. figure:: manual-10.png
         :alt: Confirm the saved blog page ID settings
         :zoom: lightbox
         :class: with-border with-shadow

   5. Frontend Routing

      The extension provides a frontend route enhancer configuration that
      you can include it in your site configuration.

      .. code-block:: yaml
         :linenos:

         imports:
            - { resource: "EXT:blog/Configuration/Routes/Default.yaml" }

      .. tip::

         You can find your site configuration in **./config/sites/<identifier>/config.yaml**

   6. Add Plugins to Blog Pages

      Example: Blog Listing

      1. Select the Page module
      2. Select the Blog page
      3. Click the Button to add content

      .. figure:: manual-11.png
         :alt: Add a content element to the blog list page
         :zoom: lightbox
         :class: with-border with-shadow

      4. Select the "Blog" tab
      5. Add plugin "Blog: List of posts"

      .. figure:: manual-12.png
         :alt: Select the Blog List of posts plugin
         :zoom: lightbox
         :class: with-border with-shadow

      +------------+------------------------+
      | Page       | Plugin                 |
      +============+========================+
      | Blog       | Blog: List of posts    |
      +------------+------------------------+
      | Authors    | Blog: List by author   |
      +------------+------------------------+
      | Categories | Blog: List by category |
      +------------+------------------------+
      | Tags       | Blog: List by tag      |
      +------------+------------------------+
      | Archive    | Blog: Archive          |
      +------------+------------------------+

   7. Congratulations

      Your blog is now ready. Continue with the guides about adding a blog
      post and configuring the extension.

      .. figure:: manual-13.png
         :alt: View the manually configured blog in the frontend
         :zoom: lightbox
         :class: with-border with-shadow
