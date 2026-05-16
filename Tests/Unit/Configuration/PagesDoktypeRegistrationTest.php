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
        $this->originalTca = is_array($GLOBALS['TCA'] ?? null) ? $GLOBALS['TCA'] : [];
        $this->originalTypo3ConfVars = is_array($GLOBALS['TYPO3_CONF_VARS'] ?? null) ? $GLOBALS['TYPO3_CONF_VARS'] : [];

        if (!defined('TYPO3')) {
            define('TYPO3', true);
        }

        $GLOBALS['TYPO3_CONF_VARS'] = $this->originalTypo3ConfVars;
        $GLOBALS['TYPO3_CONF_VARS']['FE'] = is_array($GLOBALS['TYPO3_CONF_VARS']['FE'] ?? null)
            ? $GLOBALS['TYPO3_CONF_VARS']['FE']
            : [];
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
        $pagesTca = require self::getCorePagesTcaPath();
        if (!is_array($pagesTca)) {
            self::fail('Core pages TCA must return an array.');
        }

        $GLOBALS['TCA'] = ['pages' => $pagesTca];
        require self::getExtensionPath() . '/Configuration/TCA/Overrides/pages.php';

        $pages = $GLOBALS['TCA']['pages'];

        $columns = $pages['columns'] ?? [];
        if (!is_array($columns)) {
            self::fail('Pages columns TCA must be an array.');
        }

        $doktype = $columns['doktype'] ?? [];
        if (!is_array($doktype)) {
            self::fail('pages.doktype TCA must be an array.');
        }

        $config = $doktype['config'] ?? [];
        if (!is_array($config)) {
            self::fail('pages.doktype config must be an array.');
        }

        $items = $config['items'] ?? [];
        if (!is_array($items)) {
            self::fail('pages.doktype items must be an array.');
        }

        self::assertSame(
            'LLL:EXT:blog/Resources/Private/Language/locallang_tca.xlf:pages.doktype.blog-post',
            self::findDoktypeLabel($items, Constants::DOKTYPE_BLOG_POST),
        );
        self::assertSame(
            'LLL:EXT:blog/Resources/Private/Language/locallang_tca.xlf:pages.doktype.blog-page',
            self::findDoktypeLabel($items, Constants::DOKTYPE_BLOG_PAGE),
        );
    }

    /**
     * @param array<mixed> $items
     */
    private static function findDoktypeLabel(array $items, int $doktype): ?string
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $value = $item['value'] ?? null;
            if (!is_scalar($value) || (string)$value !== (string)$doktype) {
                continue;
            }

            $label = $item['label'] ?? null;
            return is_string($label) ? $label : null;
        }

        return null;
    }
}
