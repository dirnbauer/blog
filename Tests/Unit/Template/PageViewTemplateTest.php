<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Template;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PageViewTemplateTest extends TestCase
{
    private static function getTemplateBase(): string
    {
        return dirname(__DIR__, 3) . '/Resources/Private/Templates';
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pageViewTemplateProvider(): array
    {
        $base = self::getTemplateBase();

        return [
            'Pages/BlogList' => [$base . '/Pages/BlogList.fluid.html'],
            'Pages/BlogPost' => [$base . '/Pages/BlogPost.fluid.html'],
            'ModernTailwind/Pages/BlogList' => [$base . '/ModernTailwind/Pages/BlogList.fluid.html'],
            'ModernTailwind/Pages/BlogPost' => [$base . '/ModernTailwind/Pages/BlogPost.fluid.html'],
            'ModernBootstrap/Pages/BlogList' => [$base . '/ModernBootstrap/Pages/BlogList.fluid.html'],
            'ModernBootstrap/Pages/BlogPost' => [$base . '/ModernBootstrap/Pages/BlogPost.fluid.html'],
        ];
    }

    #[Test]
    #[DataProvider('pageViewTemplateProvider')]
    public function pageViewTemplateExists(string $path): void
    {
        self::assertFileExists($path);
    }

    #[Test]
    #[DataProvider('pageViewTemplateProvider')]
    public function pageViewTemplateUsesContentAreaViewHelper(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringContainsString(
            '<f:layout name="Pages/Default" />',
            $content,
            'PAGEVIEW templates must use the Camino-style Pages/Default layout.'
        );
        self::assertStringContainsString(
            '<f:render.contentArea contentArea="{blogContentAreas.content}"',
            $content,
            'PAGEVIEW templates must render the named "content" area.'
        );
    }

    #[Test]
    #[DataProvider('pageViewTemplateProvider')]
    public function pageViewTemplateDoesNotUseLegacyDynamicContentHelper(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'lib.dynamicContent',
            $content,
            'PAGEVIEW templates must not fall back to lib.dynamicContent.'
        );
    }
}
