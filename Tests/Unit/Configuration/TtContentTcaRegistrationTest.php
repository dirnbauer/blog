<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TtContentTcaRegistrationTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalTca = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('TYPO3')) {
            define('TYPO3', true);
        }

        $this->originalTca = $GLOBALS['TCA'] ?? [];
        $GLOBALS['TCA'] = [
            'tt_content' => [
                'ctrl' => [
                    'typeicon_classes' => [],
                ],
                'columns' => [
                    'CType' => [
                        'config' => [
                            'items' => [],
                        ],
                    ],
                ],
                'types' => [
                    'header' => [
                        'showitem' => '--palette--;;headers',
                    ],
                ],
                'palettes' => [
                    'headers' => [
                        'showitem' => 'header',
                    ],
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        $GLOBALS['TCA'] = $this->originalTca;

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function registeredTypeProvider(): array
    {
        return [
            'posts' => ['blog_posts'],
            'latest posts' => ['blog_latestposts'],
            'category' => ['blog_category'],
            'author posts' => ['blog_authorposts'],
            'tag' => ['blog_tag'],
            'archive' => ['blog_archive'],
            'sidebar' => ['blog_sidebar'],
            'comment form' => ['blog_commentform'],
            'comments' => ['blog_comments'],
            'authors' => ['blog_authors'],
            'demanded posts' => ['blog_demandedposts'],
            'related posts' => ['blog_relatedposts'],
            'header' => ['blog_header'],
            'footer' => ['blog_footer'],
        ];
    }

    #[Test]
    #[DataProvider('registeredTypeProvider')]
    public function ttContentOverrideKeepsRegisteredTypes(string $typeName): void
    {
        require dirname(__DIR__, 3) . '/Configuration/TCA/Overrides/tt_content.php';

        self::assertArrayHasKey(
            $typeName,
            $GLOBALS['TCA']['tt_content']['types'],
            sprintf('The tt_content override must keep the "%s" CType registered.', $typeName)
        );
    }

    #[Test]
    public function demandedPostsTypeKeepsItsFlexFormConfiguration(): void
    {
        require dirname(__DIR__, 3) . '/Configuration/TCA/Overrides/tt_content.php';

        self::assertSame(
            'FILE:EXT:blog/Configuration/FlexForms/Demand.xml',
            $GLOBALS['TCA']['tt_content']['types']['blog_demandedposts']['columnsOverrides']['pi_flexform']['config']['ds']['default'] ?? null,
            'The blog_demandedposts CType must keep its FlexForm data structure.'
        );
    }
}
