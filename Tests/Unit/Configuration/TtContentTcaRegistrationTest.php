<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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

        $existingTca = $GLOBALS['TCA'] ?? [];
        $this->originalTca = is_array($existingTca) ? $existingTca : [];
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
        $types = $this->loadTtContentTypes();
        self::assertArrayHasKey(
            $typeName,
            $types,
            sprintf('The tt_content override must keep the "%s" CType registered.', $typeName),
        );
    }

    #[Test]
    public function demandedPostsTypeKeepsItsFlexFormConfiguration(): void
    {
        $types = $this->loadTtContentTypes();
        self::assertArrayHasKey('blog_demandedposts', $types);
        $blogDemandedPosts = $types['blog_demandedposts'];
        self::assertIsArray($blogDemandedPosts);
        $columnsOverrides = $blogDemandedPosts['columnsOverrides'] ?? null;
        self::assertIsArray($columnsOverrides);
        $piFlexform = $columnsOverrides['pi_flexform'] ?? null;
        self::assertIsArray($piFlexform);
        $config = $piFlexform['config'] ?? null;
        self::assertIsArray($config);
        $ds = $config['ds'] ?? null;
        self::assertIsArray($ds);
        self::assertSame(
            'FILE:EXT:blog/Configuration/FlexForms/Demand.xml',
            $ds['default'] ?? null,
            'The blog_demandedposts CType must keep its FlexForm data structure.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function loadTtContentTypes(): array
    {
        require dirname(__DIR__, 3) . '/Configuration/TCA/Overrides/tt_content.php';

        $tca = $GLOBALS['TCA'] ?? null;
        self::assertIsArray($tca);
        self::assertArrayHasKey('tt_content', $tca);
        $ttContent = $tca['tt_content'];
        self::assertIsArray($ttContent);
        self::assertArrayHasKey('types', $ttContent);
        $types = $ttContent['types'];
        self::assertIsArray($types);

        $typed = [];
        foreach ($types as $key => $value) {
            $typed[(string) $key] = $value;
        }

        return $typed;
    }
}
