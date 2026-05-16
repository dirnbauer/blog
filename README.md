# TYPO3 Blog Extension

[![TYPO3 14](https://img.shields.io/badge/TYPO3-14.x-orange.svg)](https://get.typo3.org/version/14)
[![PHP](https://img.shields.io/badge/PHP-8.2--8.4-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL%202.0-green.svg)](LICENSE)

A blog extension for TYPO3 built entirely on core concepts — pages as posts, content elements for
article bodies, and categories/tags for organization. If you know TYPO3, you already know how to
use this blog.

> **Upgrading a sitepackage from a previous version?** The `renderPlugin`
> section in the shipped templates moved to `tt_content.{listType}.20` for
> TYPO3 v14 workspace safety. See
> [Development: Template migration notes](https://docs.typo3.org/p/t3g/blog/master/en-us/Development/Index.html#template-migration-notes)
> for the rationale and the one-line template change.

## Requirements

| Blog Extension | TYPO3      | PHP       |
|----------------|------------|-----------|
| 14.x           | 14.3-14.x  | 8.2-8.4   |

## Features

- **Pages as blog posts** — Blog entries are pages with a dedicated page type (doktype 137).
  Create and manage them in the page module like any other page.
- **All content elements** — Use every content element and backend layout you already have.
  No proprietary content model.
- **Categories and tags** — Organize posts with TYPO3 system categories and custom tags.
  Filter and list by category, tag, author, or date.
- **Authors** — Multi-author support with avatars (Gravatar or uploaded image), social links,
  bio, and dedicated author pages.
- **Comments** — Built-in comment system with moderation workflow
  (pending/approved/declined/deleted), optional Google reCAPTCHA, optional Disqus,
  and email notifications.
- **Workspace support** — Full TYPO3 Workspaces integration. Stage blog posts, tags, and
  authors before publishing. Comments remain live-editable.
- **20 Extbase plugins** — 14 editor-selectable content elements plus 6 widget/feed
  renderers for sidebars and RSS feeds.
- **3 backend modules** — Dedicated modules for post overview, comment management, and
  blog setup wizard.
- **Site sets and customizable templates** — Use the `blog/standalone`,
  `blog/integration`, or `blog/bootstrap-53` site sets.
  Override any Fluid template in your sitepackage.
- **RSS feeds** — Built-in feed support with featured images.
- **Routing** — Ships route enhancers for posts, categories, tags, authors, archive,
  pagination, and feed URLs.
- **SEO** — Sets page titles plus description, Open Graph, and Twitter description/title
  tags for archive, category, author, and tag listings.

## Installation

```bash
composer require t3g/blog
```

Then add a Blog site set to your site configuration:

- `blog/standalone` for a generated standalone blog page tree
- `blog/integration` for integration into an existing site
- `blog/bootstrap-53` when you want the shipped Bootstrap 5.3 frontend templates

For detailed setup instructions, see the [documentation](https://docs.typo3.org/p/t3g/blog/master/en-us/).
For local contribution and testing, see the
[development guide](https://docs.typo3.org/p/t3g/blog/master/en-us/Development/Index.html).

## Quick Start

1. Install via Composer
2. Go to **Blog > Setup** in the TYPO3 backend
3. Use the Setup Wizard to create a fully configured blog instance
4. Start writing posts

For manual integration into an existing site, see the
[Manual Setup guide](https://docs.typo3.org/p/t3g/blog/master/en-us/Setup/Manual/Index.html).

## Workspace Support

The extension supports TYPO3 Workspaces for editorial staging workflows:

| Table | Behavior |
|-------|----------|
| Blog posts (pages) | Fully versioned |
| Tags | Fully versioned |
| Authors | Fully versioned |
| Comments | Always live-editable |
| Categories | Fully versioned (core) |

See the [Workspace documentation](https://docs.typo3.org/p/t3g/blog/master/en-us/Workspaces/Index.html)
for details.

## Configuration Notes

- Include `EXT:blog/Configuration/Routes/Default.yaml` in your site configuration
  when you set up a blog manually. The setup wizard writes this import for generated
  standalone blogs.
- The `blog/standalone` set loads Bootstrap 5.3 from jsDelivr with SRI attributes.
  Self-host those assets or adapt your CSP when `default-src 'self'` is enforced.
- Gravatar proxying is disabled by default. Enable `enableGravatarProxy` in extension
  configuration if avatars should be fetched by TYPO3 and served from `typo3temp`.

## Development

The repository no longer ships a tracked DDEV setup. Use any local TYPO3 v14
environment with PHP 8.2-8.4, MySQL or MariaDB, Composer, and Node.js/npm.

```bash
# Install dependencies
composer update
npm ci

# Build frontend assets
npm run build

# Run PHP test suites
composer test:php:lint
composer test:php:unit
composer test:php:functional

# Static analysis
composer phpstan

# Code style
composer cgl
composer cgl:fix

# CI-equivalent dispatcher
Build/Scripts/runTests.sh -s all
```

### Functional tests

The functional test runner defaults to a local MySQL or MariaDB instance on
`127.0.0.1:3306` with database `t3func` and credentials `root` / `root`.
It also defaults `TYPO3_PATH_APP` to `.build` and `TYPO3_PATH_ROOT` to
`.build/public`.

Override those variables when your setup differs:

```bash
export typo3DatabaseHost=127.0.0.1
export typo3DatabasePort=3306
export typo3DatabaseName=t3func
export typo3DatabaseUsername=root
export typo3DatabasePassword=root
export typo3DatabaseDriver=mysqli

composer test:php:functional
```

### Browser smoke tests

Playwright smoke tests target any running TYPO3 instance and are not tied to a
specific local stack:

```bash
export BLOG_BASE_URL=https://example.test
export BLOG_LIST_PATH=/blog1/
export BLOG_POST_PATH=/blog1/first-blog-post

npm run playwright:install
npm run test:e2e
```

## Contributing

- Report bugs and request features on [GitHub](https://github.com/TYPO3GmbH/blog/issues)
- Join `#t3g-ext-blog` on [TYPO3 Slack](https://typo3.slack.com/archives/t3g-ext-blog)
- Pull requests welcome — fork, branch, and submit

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
