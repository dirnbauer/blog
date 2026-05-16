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

final class VisualEditorTemplateTest extends TestCase
{
    private static function getTemplateBase(): string
    {
        return dirname(__DIR__, 3) . '/Resources/Private';
    }

    /**
     * @return array<string, array{0: string, 1: non-empty-list<string>}>
     */
    public static function frontendTemplateProvider(): array
    {
        $base = self::getTemplateBase();

        return [
            'post header' => [
                $base . '/Templates/Post/Header.html',
                [
                    "{post -> f:render.text(field: 'title')}",
                ],
            ],
            'post list item' => [
                $base . '/Partials/List/Post.html',
                [
                    "{post -> f:render.text(field: 'abstract')}",
                    "{post -> f:render.text(field: 'description')}",
                ],
            ],
            'post teaser item' => [
                $base . '/Partials/Teaser/Post.html',
                [
                    "{post -> f:render.text(field: 'abstract')}",
                    "{post -> f:render.text(field: 'description')}",
                ],
            ],
            'full author partial' => [
                $base . '/Partials/Post/Author.html',
                [
                    "{author -> f:render.text(field: 'name')}",
                    "{author -> f:render.text(field: 'title')}",
                    "{author -> f:render.text(field: 'location')}",
                    "{author -> f:render.text(field: 'bio')}",
                ],
            ],
            'author archive header' => [
                $base . '/Templates/Post/ListPostsByAuthor.html',
                [
                    "{author -> f:render.text(field: 'name')}",
                    "{author -> f:render.text(field: 'bio')}",
                ],
            ],
            'category archive header' => [
                $base . '/Templates/Post/ListPostsByCategory.html',
                [
                    "{category -> f:render.text(field: 'title')}",
                    "{category -> f:render.text(field: 'description')}",
                ],
            ],
            'tag archive header' => [
                $base . '/Templates/Post/ListPostsByTag.html',
                [
                    "{tag -> f:render.text(field: 'title')}",
                    "{tag -> f:render.text(field: 'description')}",
                ],
            ],
            'post meta authors' => [
                $base . '/Partials/Meta/Elements/Authors.html',
                [
                    "{author -> f:render.text(field: 'name')}",
                ],
            ],
            'category widget visible label' => [
                $base . '/Templates/Widget/Categories.html',
                [
                    "{category -> f:render.text(field: 'title')}",
                ],
            ],
            'recent posts widget visible label' => [
                $base . '/Templates/Widget/RecentPosts.html',
                [
                    "{post -> f:render.text(field: 'title')}",
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pageContentAreaTemplateProvider(): array
    {
        $base = self::getTemplateBase();

        return [
            'default blog list page' => [$base . '/Templates/Pages/BlogList.fluid.html'],
            'default blog post page' => [$base . '/Templates/Pages/BlogPost.fluid.html'],
            'bootstrap 5.3 blog list page' => [$base . '/Templates/Bootstrap53/Pages/BlogList.fluid.html'],
            'bootstrap 5.3 blog post page' => [$base . '/Templates/Bootstrap53/Pages/BlogPost.fluid.html'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: non-empty-list<string>}>
     */
    public static function bootstrap53TemplateProvider(): array
    {
        $base = self::getTemplateBase();

        return [
            'bootstrap 5.3 page layout' => [
                $base . '/Templates/Bootstrap53/Pages/Default/default.fluid.html',
                [
                    "{item.record -> f:render.text(field: 'nav_title')}",
                    "{item.record -> f:render.text(field: 'title')}",
                ],
            ],
        ];
    }

    /**
     * @param non-empty-list<string> $expectedSnippets
     */
    #[Test]
    #[DataProvider('frontendTemplateProvider')]
    public function frontendTemplatesRenderEditableTextFieldsWithRenderText(string $templatePath, array $expectedSnippets): void
    {
        self::assertFileExists($templatePath);

        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        foreach ($expectedSnippets as $expectedSnippet) {
            self::assertStringContainsString(
                $expectedSnippet,
                $content,
                sprintf(
                    'Frontend template %s should render TCA-backed text field output through f:render.text for Visual Editor compatibility.',
                    $templatePath,
                ),
            );
        }
    }

    #[Test]
    #[DataProvider('pageContentAreaTemplateProvider')]
    public function pageTemplatesExposeEditableContentArea(string $templatePath): void
    {
        self::assertFileExists($templatePath);

        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        self::assertStringContainsString(
            '<f:render.contentArea contentArea="{blogContentAreas.content}"',
            $content,
            sprintf(
                'PAGEVIEW template %s should expose the backend layout "content" column through f:render.contentArea for Visual Editor drag/drop and add/delete support.',
                $templatePath,
            ),
        );
        self::assertStringNotContainsString(
            'lib.dynamicContent',
            $content,
            sprintf(
                'PAGEVIEW template %s should not use legacy dynamic content rendering for Visual Editor content areas.',
                $templatePath,
            ),
        );
    }

    /**
     * @param non-empty-list<string> $expectedSnippets
     */
    #[Test]
    #[DataProvider('bootstrap53TemplateProvider')]
    public function bootstrap53TemplatesRenderEditableTextFieldsWithRenderText(string $templatePath, array $expectedSnippets): void
    {
        self::assertFileExists($templatePath);

        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        foreach ($expectedSnippets as $expectedSnippet) {
            self::assertStringContainsString(
                $expectedSnippet,
                $content,
                sprintf(
                    'Bootstrap 5.3 template %s should render TCA-backed page fields through f:render.text for Visual Editor compatibility.',
                    $templatePath,
                ),
            );
        }
    }
}
