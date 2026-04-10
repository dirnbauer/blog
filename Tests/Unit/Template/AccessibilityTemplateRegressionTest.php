<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Template;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccessibilityTemplateRegressionTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pageTemplateProvider(): array
    {
        $base = self::getExtensionPath() . '/Resources/Private/Templates';
        $paths = [
            'Page/BlogList' => $base . '/Page/BlogList.html',
            'Page/BlogPost' => $base . '/Page/BlogPost.html',
            'Pages/BlogList' => $base . '/Pages/BlogList.fluid.html',
            'Pages/BlogPost' => $base . '/Pages/BlogPost.fluid.html',
            'ModernBootstrap/Page/BlogList' => $base . '/ModernBootstrap/Page/BlogList.html',
            'ModernBootstrap/Page/BlogPost' => $base . '/ModernBootstrap/Page/BlogPost.html',
            'ModernBootstrap/Pages/BlogList' => $base . '/ModernBootstrap/Pages/BlogList.fluid.html',
            'ModernBootstrap/Pages/BlogPost' => $base . '/ModernBootstrap/Pages/BlogPost.fluid.html',
            'ModernTailwind/Page/BlogList' => $base . '/ModernTailwind/Page/BlogList.html',
            'ModernTailwind/Page/BlogPost' => $base . '/ModernTailwind/Page/BlogPost.html',
            'ModernTailwind/Pages/BlogList' => $base . '/ModernTailwind/Pages/BlogList.fluid.html',
            'ModernTailwind/Pages/BlogPost' => $base . '/ModernTailwind/Pages/BlogPost.fluid.html',
        ];

        return array_map(static fn(string $path): array => [$path], $paths);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function layoutTemplateProvider(): array
    {
        $base = self::getExtensionPath() . '/Resources/Private';
        $paths = [
            'Layouts/Page/Default' => $base . '/Layouts/Page/Default.html',
            'Templates/Pages/Default/default' => $base . '/Templates/Pages/Default/default.fluid.html',
            'Templates/Layouts/Pages/Default' => $base . '/Templates/Layouts/Pages/Default.fluid.html',
            'ModernBootstrap/Layouts/Page/Default' => $base . '/Templates/ModernBootstrap/Layouts/Page/Default.html',
            'ModernBootstrap/Pages/Default/default' => $base . '/Templates/ModernBootstrap/Pages/Default/default.fluid.html',
            'ModernBootstrap/Layouts/Pages/Default' => $base . '/Templates/ModernBootstrap/Layouts/Pages/Default.fluid.html',
            'ModernTailwind/Layouts/Page/Default' => $base . '/Templates/ModernTailwind/Layouts/Page/Default.html',
            'ModernTailwind/Pages/Default/default' => $base . '/Templates/ModernTailwind/Pages/Default/default.fluid.html',
            'ModernTailwind/Layouts/Pages/Default' => $base . '/Templates/ModernTailwind/Layouts/Pages/Default.fluid.html',
        ];

        return array_map(static fn(string $path): array => [$path], $paths);
    }

    #[Test]
    #[DataProvider('pageTemplateProvider')]
    public function pageTemplatesDoNotDefineNestedMainLandmarks(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'role="main"',
            $content,
            'Page templates must rely on the shared layout main landmark instead of nesting role="main".'
        );
    }

    #[Test]
    #[DataProvider('layoutTemplateProvider')]
    public function layoutsProvideSkipLinkAndMainTarget(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content);

        self::assertStringContainsString('class="blog-skip-link"', $content);
        self::assertStringContainsString('id="main-content"', $content);
    }

    #[Test]
    public function paginationTemplateAvoidsJavascriptPlaceholdersAndMarksCurrentPage(): void
    {
        $path = self::getExtensionPath() . '/Resources/Private/Partials/Pagination/Pagination.html';
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString('javascript:void();', $content);
        self::assertStringContainsString('aria-current="page"', $content);
    }

    #[Test]
    public function frontendAvatarImagesDeclareDecorativeAltText(): void
    {
        $paths = [
            self::getExtensionPath() . '/Resources/Private/Partials/List/Author.html',
            self::getExtensionPath() . '/Resources/Private/Partials/Post/Author.html',
            self::getExtensionPath() . '/Resources/Private/Partials/Meta/Elements/Authors.html',
        ];

        foreach ($paths as $path) {
            $content = file_get_contents($path);
            self::assertNotFalse($content);
            self::assertStringContainsString('alt=""', $content, basename($path) . ' must provide decorative alt text.');
        }
    }
}
