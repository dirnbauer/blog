# TYPO3 Blog Extension

[![TYPO3 14](https://img.shields.io/badge/TYPO3-14.x-orange.svg)](https://get.typo3.org/version/14)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.2-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL%202.0-green.svg)](LICENSE)

A blog extension for TYPO3 built entirely on core concepts — pages as posts, content elements for
article bodies, and categories/tags for organization. If you know TYPO3, you already know how to
use this blog.

## ⚠️ Breaking Changes for TYPO3 v14

### Blog page templates: new plugin rendering approach

The `renderPlugin` section in the shipped blog page templates has changed.
This affects both the legacy integration templates under `Templates/Page/*.html`
and the TYPO3 v14 `PAGEVIEW` templates under `Templates/Pages/*.fluid.html`.

**New (v14-compatible, workspace-safe):**

```html
<f:section name="renderPlugin">
    <f:cObject typoscriptObjectPath="tt_content.{listType}.20" />
</f:section>
```

**Why:** TYPO3 v14 added the `record-transformation` data processor to
`lib.contentElement`. It requires all system fields (`sys_language_uid`,
`l18n_parent`, `t3ver_wsid`, `header`, …) on every `tt_content` row. The old
approach rendered synthetic `tt_content` rows that lacked these fields, causing
`IncompleteRecordException`. The new approach renders the `EXTBASEPLUGIN`
content object directly, bypassing the content-element pipeline entirely.

**Action required:** If your sitepackage overrides `BlogList` / `BlogPost`
templates in either `Page/*.html` or `Pages/*.fluid.html` (including the
`ModernTailwind` / `ModernBootstrap` variants), update the `renderPlugin`
section to use the new pattern shown above.

For TYPO3 v14 standalone rendering, the recommended templates are the
`PAGEVIEW` files in `Resources/Private/Templates/Pages/*.fluid.html` together
with `Resources/Private/Templates/Layouts/Pages/Default.fluid.html`.

**Removal:** The legacy synthetic `tt_content` rendering pattern is no longer
part of the supported rendering path. Template overrides still using that
approach must be updated to `tt_content.{listType}.20`.

## Requirements

| Blog Extension | TYPO3          | PHP    |
|----------------|----------------|--------|
| 14.x           | 14.x           | >= 8.2 |

## Features

- **Pages as blog posts** — Blog entries are pages with a dedicated page type (doktype 137).
  Create and manage them in the page module like any other page.
- **All content elements** — Use every content element and backend layout you already have.
  No proprietary content model.
- **Categories and tags** — Organize posts with TYPO3 system categories and custom tags.
  Filter and list by category, tag, author, or date.
- **Authors** — Multi-author support with avatars (Gravatar or uploaded image), social links,
  bio, and dedicated author pages.
- **Comments** — Built-in comment system with moderation workflow (pending/approved/declined),
  Google reCAPTCHA support, and email notifications.
- **Workspace support** — Full TYPO3 Workspaces integration. Stage blog posts, tags, and
  authors before publishing. Comments remain live-editable.
- **20+ plugins** — List posts, sidebar, archive, related posts, header/footer, comment form,
  and widget plugins — all usable as standalone content elements.
- **3 backend modules** — Dedicated modules for post overview, comment management, and
  blog setup wizard.
- **Customizable templates** — Fluid-based templates. Override any template in your sitepackage.
- **RSS feeds** — Built-in feed support with featured images.
- **Routing** — Ships with frontend route enhancers for clean URLs.
- **SEO** — Structured data, meta tags, and social sharing support.

## Installation

```bash
composer require t3g/blog
```

Then add the Blog site set to your site configuration.

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

## Development

The repository no longer ships a tracked DDEV setup. Use any local TYPO3 v14
environment with PHP 8.2+, MySQL or MariaDB, Composer, and Node.js.

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
```

### Functional tests

The functional test runner defaults to a local MySQL or MariaDB instance on
`127.0.0.1:3306` with database `t3func` and credentials `root` / `root`.

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
