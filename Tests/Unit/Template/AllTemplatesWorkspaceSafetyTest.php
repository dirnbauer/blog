<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Template;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guard blog page templates against workspace-unsafe rendering patterns.
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
        $bootstrap53PagesTemplates = glob($base . '/Bootstrap53/Pages/Blog*.html');

        return array_merge(
            $pageTemplates === false ? [] : $pageTemplates,
            $pagesTemplates === false ? [] : $pagesTemplates,
            $bootstrap53PagesTemplates === false ? [] : $bootstrap53PagesTemplates,
        );
    }

    #[Test]
    public function blogPageTemplatesNeverUseSyntheticTtContentRendering(): void
    {
        foreach (self::getBlogPageTemplates() as $path) {
            $content = file_get_contents($path);
            self::assertNotFalse($content, $path);

            self::assertDoesNotMatchRegularExpression(
                '/data="[^"]*"[^>]*table="tt_content"/',
                $content,
                basename($path) . ' must not pass synthetic data with table="tt_content".',
            );
            self::assertStringNotContainsString(
                'contentListOptions',
                $content,
                basename($path) . ' must not use the removed synthetic content helper.',
            );
            self::assertStringNotContainsString(
                'contentObjectData',
                $content,
                basename($path) . ' must not reference {contentObjectData}.',
            );
        }
    }

    #[Test]
    public function allPageTemplatesUseDirectDot20RenderPath(): void
    {
        foreach (self::getBlogPageTemplates() as $path) {
            $content = file_get_contents($path);
            self::assertNotFalse($content);

            self::assertStringContainsString(
                '<f:section name="renderPlugin">',
                $content,
                basename($path) . ' must contain renderPlugin section.',
            );

            if (preg_match('/<f:section name="renderPlugin">(.*?)<\/f:section>/s', $content, $match) === 1) {
                self::assertStringContainsString(
                    'tt_content.{listType}.20',
                    $match[1],
                    basename($path) . ' must use tt_content.{listType}.20.',
                );
                self::assertStringNotContainsString('data=', $match[1], basename($path) . ' renderPlugin must not pass data.');
                self::assertStringNotContainsString('table=', $match[1], basename($path) . ' renderPlugin must not pass table.');
            }
        }
    }

    #[Test]
    public function blogPostTemplateRendersRequiredPlugins(): void
    {
        $required = [
            'blog_header',
            'blog_footer',
            'blog_authors',
            'blog_comments',
            'blog_commentform',
            'blog_relatedposts',
            'blog_sidebar',
        ];

        foreach (self::getBlogPageTemplates() as $path) {
            if (!str_contains($path, 'BlogPost')) {
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
}
