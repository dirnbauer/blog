<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\Blog\Constants;

final class PagesDoktypeRegistrationTest extends TestCase
{
    private array $originalTca = [];
    private array $originalTypo3ConfVars = [];

    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function getCorePagesTcaPath(): string
    {
        return self::getExtensionPath() . '/.build/vendor/typo3/cms-core/Configuration/TCA/pages.php';
    }

    protected function setUp(): void
    {
        $this->originalTca = $GLOBALS['TCA'] ?? [];
        $this->originalTypo3ConfVars = $GLOBALS['TYPO3_CONF_VARS'] ?? [];

        if (!defined('TYPO3')) {
            define('TYPO3', true);
        }

        $GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] = false;
    }

    protected function tearDown(): void
    {
        $GLOBALS['TCA'] = $this->originalTca;
        $GLOBALS['TYPO3_CONF_VARS'] = $this->originalTypo3ConfVars;
    }

    #[Test]
    public function pagesOverrideKeepsCustomDoktypeItemsRegistered(): void
    {
        $GLOBALS['TCA']['pages'] = require self::getCorePagesTcaPath();
        require self::getExtensionPath() . '/Configuration/TCA/Overrides/pages.php';

        $items = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [];
        $itemsByValue = [];

        foreach ($items as $item) {
            $itemsByValue[(string)($item['value'] ?? '')] = $item;
        }

        self::assertArrayHasKey(
            (string)Constants::DOKTYPE_BLOG_POST,
            $itemsByValue,
            'Blog post doktype must remain in the pages.doktype selector.',
        );
        self::assertSame(
            'LLL:EXT:blog/Resources/Private/Language/locallang_tca.xlf:pages.doktype.blog-post',
            $itemsByValue[(string)Constants::DOKTYPE_BLOG_POST]['label'] ?? null,
        );
        self::assertArrayHasKey(
            (string)Constants::DOKTYPE_BLOG_PAGE,
            $itemsByValue,
            'Blog page doktype must remain in the pages.doktype selector.',
        );
        self::assertSame(
            'LLL:EXT:blog/Resources/Private/Language/locallang_tca.xlf:pages.doktype.blog-page',
            $itemsByValue[(string)Constants::DOKTYPE_BLOG_PAGE]['label'] ?? null,
        );
    }
}
