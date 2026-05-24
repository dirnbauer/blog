<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Updates;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[UpgradeWizard(ListTypeMigration::class)]
final class ListTypeMigration extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        $ctypes = [
            'blog_posts',
            'blog_latestposts',
            'blog_category',
            'blog_authorposts',
            'blog_tag',
            'blog_archive',
            'blog_sidebar',
            'blog_commentform',
            'blog_comments',
            'blog_authors',
            'blog_demandedposts',
            'blog_relatedposts',
            'blog_header',
            'blog_footer',
        ];
        return array_combine($ctypes, $ctypes);
    }

    public function getTitle(): string
    {
        return LocalizationUtility::translate('updates.list_type.title', 'Blog') ?? 'updates.list_type.title';
    }

    public function getDescription(): string
    {
        return LocalizationUtility::translate('updates.list_type.description', 'Blog') ?? 'updates.list_type.description';
    }
}
