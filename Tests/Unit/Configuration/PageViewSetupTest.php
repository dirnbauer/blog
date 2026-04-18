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

final class PageViewSetupTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    #[Test]
    public function standaloneSetUsesPageView(): void
    {
        $content = file_get_contents(self::getExtensionPath() . '/Configuration/Sets/Standalone/setup.typoscript');
        self::assertNotFalse($content);

        self::assertStringContainsString('lib.fluidPage = PAGEVIEW', $content);
        self::assertStringContainsString('contentAs = blogContentAreas', $content);
        self::assertStringContainsString('10 < lib.fluidPage', $content);
        self::assertStringNotContainsString('10 = FLUIDTEMPLATE', $content);
    }

    #[Test]
    public function modernTailwindSetUsesPageView(): void
    {
        $content = file_get_contents(self::getExtensionPath() . '/Configuration/Sets/ModernTailwind/setup.typoscript');
        self::assertNotFalse($content);

        self::assertStringContainsString('lib.fluidPage = PAGEVIEW', $content);
        self::assertStringContainsString('contentAs = blogContentAreas', $content);
        self::assertStringContainsString('paths.10 = EXT:blog/Resources/Private/Templates/ModernTailwind/', $content);
        self::assertStringNotContainsString('10 = FLUIDTEMPLATE', $content);
    }

    #[Test]
    public function modernBootstrapOverridesPageViewPaths(): void
    {
        $content = file_get_contents(self::getExtensionPath() . '/Configuration/Sets/ModernBootstrap/setup.typoscript');
        self::assertNotFalse($content);

        self::assertStringContainsString(
            'lib.fluidPage.paths.10 = EXT:blog/Resources/Private/Templates/ModernBootstrap/',
            $content,
        );
    }

    #[Test]
    public function pageTsConfigDefinesBackendLayoutDefaults(): void
    {
        $content = file_get_contents(self::getExtensionPath() . '/Configuration/PageTsConfig/BlogLayouts.tsconfig');
        self::assertNotFalse($content);

        self::assertStringContainsString("@import 'EXT:blog/Configuration/BackendLayouts/*.tsconfig'", $content);
        self::assertStringContainsString('137 = pagets__BlogPost', $content);
        self::assertStringContainsString('138 = pagets__BlogList', $content);
        self::assertStringNotContainsString('backend_layout_next_level', $content);
    }
}
