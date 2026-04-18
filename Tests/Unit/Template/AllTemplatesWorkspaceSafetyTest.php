<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Template;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verify that NONE of the blog Fluid templates use patterns that break
 * when TYPO3 Workspaces is active (record-transformation requiring
 * t3ver_wsid, t3ver_oid, t3ver_state, t3ver_stage on every tt_content row)
 * OR when Workspaces is not installed at all.
 */
final class AllTemplatesWorkspaceSafetyTest extends TestCase
{
    private static function getTemplateBase(): string
    {
        return dirname(__DIR__, 3) . '/Resources/Private/Templates';
    }

    /**
     * @return list<string>
     */
    private static function getBlogPageTemplates(): array
    {
        $base = self::getTemplateBase();
        $pageTemplates = glob($base . '/Page/Blog*.html');
        $pagesTemplates = glob($base . '/Pages/Blog*.html');
        $modernTailwindPageTemplates = glob($base . '/ModernTailwind/Page/Blog*.html');
        $modernTailwindPagesTemplates = glob($base . '/ModernTailwind/Pages/Blog*.html');
        $modernBootstrapPageTemplates = glob($base . '/ModernBootstrap/Page/Blog*.html');
        $modernBootstrapPagesTemplates = glob($base . '/ModernBootstrap/Pages/Blog*.html');

        return array_merge(
            $pageTemplates === false ? [] : $pageTemplates,
            $pagesTemplates === false ? [] : $pagesTemplates,
            $modernTailwindPageTemplates === false ? [] : $modernTailwindPageTemplates,
            $modernTailwindPagesTemplates === false ? [] : $modernTailwindPagesTemplates,
            $modernBootstrapPageTemplates === false ? [] : $modernBootstrapPageTemplates,
            $modernBootstrapPagesTemplates === false ? [] : $modernBootstrapPagesTemplates,
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allFluidTemplateProvider(): array
    {
        $base = self::getTemplateBase();
        $templates = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            if ($file->getExtension() === 'html') {
                $relative = str_replace($base . '/', '', $file->getPathname());
                $templates[$relative] = [$file->getPathname()];
            }
        }
        ksort($templates);
        return $templates;
    }

    #[Test]
    #[DataProvider('allFluidTemplateProvider')]
    public function templateNeverPassesSyntheticDataWithTableTtContent(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        // data="{...}" table="tt_content" triggers record-transformation
        // which validates t3ver_* fields that synthetic data lacks.
        self::assertDoesNotMatchRegularExpression(
            '/data="[^"]*"[^>]*table="tt_content"/',
            $content,
            'Must not pass data with table="tt_content" — '
            . 'record-transformation requires workspace fields on every row.',
        );
    }

    #[Test]
    #[DataProvider('allFluidTemplateProvider')]
    public function templateDoesNotUseLegacySyntheticContentHelper(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'contentListOptions',
            $content,
            'Must not use the removed synthetic content helper — synthetic records '
            . 'miss workspace system fields (t3ver_wsid, t3ver_oid, t3ver_state).',
        );
    }

    #[Test]
    #[DataProvider('allFluidTemplateProvider')]
    public function templateDoesNotReferenceContentObjectDataVariable(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'contentObjectData',
            $content,
            'Must not reference {contentObjectData} — this variable belonged to the '
            . 'removed synthetic content rendering path.',
        );
    }

    #[Test]
    #[DataProvider('allFluidTemplateProvider')]
    public function templateDoesNotRenderTtContentCaseDirectlyWithSyntheticData(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        // The pattern <f:cObject typoscriptObjectPath="tt_content" data="..." />
        // routes through tt_content CASE → lib.contentElement → record-transformation.
        // Only tt_content.{CType}.20 (EXTBASEPLUGIN) bypasses this safely.
        self::assertDoesNotMatchRegularExpression(
            '/typoscriptObjectPath="tt_content"\s+data="/',
            $content,
            'Must not render tt_content CASE with synthetic data attribute.',
        );
    }

    #[Test]
    #[DataProvider('allFluidTemplateProvider')]
    public function templateDoesNotUseFakeUidConstants(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        // Legacy synthetic-content rendering used negative fake UIDs. These
        // should never appear directly in templates.
        self::assertDoesNotMatchRegularExpression(
            '/-16000000\d{2}/',
            $content,
            'Templates must not contain hardcoded legacy fake UID values.',
        );
    }

    #[Test]
    public function atLeastSixPageTemplatesExist(): void
    {
        $total = count(self::getBlogPageTemplates());
        self::assertGreaterThanOrEqual(6, $total, 'Expected at least 6 page templates (2 per variant).');
    }

    #[Test]
    public function allPageTemplatesHaveRenderPluginSection(): void
    {
        foreach (self::getBlogPageTemplates() as $path) {
            $content = file_get_contents($path);
            self::assertNotFalse($content);
            self::assertStringContainsString(
                '<f:section name="renderPlugin">',
                $content,
                'Page template must contain renderPlugin section: ' . basename($path),
            );
        }
    }

    #[Test]
    public function allRenderPluginSectionsUseDirectDot20Path(): void
    {
        foreach (self::getBlogPageTemplates() as $path) {
            $content = file_get_contents($path);
            self::assertNotFalse($content);

            if (preg_match('/<f:section name="renderPlugin">(.*?)<\/f:section>/s', $content, $m) === 1) {
                self::assertStringContainsString(
                    'tt_content.{listType}.20',
                    $m[1],
                    'renderPlugin must use tt_content.{listType}.20 in: ' . basename($path),
                );
                self::assertStringNotContainsString(
                    'data=',
                    $m[1],
                    'renderPlugin must NOT pass data attribute in: ' . basename($path),
                );
                self::assertStringNotContainsString(
                    'table=',
                    $m[1],
                    'renderPlugin must NOT pass table attribute in: ' . basename($path),
                );
            }
        }
    }

    #[Test]
    public function blogPostTemplateRendersAllRequiredPlugins(): void
    {
        $base = self::getTemplateBase();
        $required = [
            'blog_header',
            'blog_footer',
            'blog_authors',
            'blog_comments',
            'blog_commentform',
            'blog_relatedposts',
            'blog_sidebar',
        ];

        $blogPostPaths = [
            $base . '/Page/BlogPost.html',
            $base . '/Pages/BlogPost.fluid.html',
            $base . '/ModernTailwind/Page/BlogPost.html',
            $base . '/ModernTailwind/Pages/BlogPost.fluid.html',
            $base . '/ModernBootstrap/Page/BlogPost.html',
            $base . '/ModernBootstrap/Pages/BlogPost.fluid.html',
        ];

        foreach ($blogPostPaths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $content = file_get_contents($path);
            self::assertNotFalse($content);

            foreach ($required as $listType) {
                self::assertStringContainsString(
                    "listType: '" . $listType . "'",
                    $content,
                    sprintf('%s must render plugin %s', basename($path), $listType),
                );
            }
        }
    }

    #[Test]
    public function blogListTemplateRendersSidebarPlugin(): void
    {
        $base = self::getTemplateBase();
        $blogListPaths = [
            $base . '/Page/BlogList.html',
            $base . '/Pages/BlogList.fluid.html',
            $base . '/ModernTailwind/Page/BlogList.html',
            $base . '/ModernTailwind/Pages/BlogList.fluid.html',
            $base . '/ModernBootstrap/Page/BlogList.html',
            $base . '/ModernBootstrap/Pages/BlogList.fluid.html',
        ];

        foreach ($blogListPaths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            $content = file_get_contents($path);
            self::assertNotFalse($content);

            self::assertStringContainsString(
                "listType: 'blog_sidebar'",
                $content,
                basename($path) . ' must render blog_sidebar plugin.',
            );
        }
    }
}
