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

/**
 * Verify TypoScript setup files don't contain workspace-unsafe patterns
 * and don't hard-depend on the workspaces extension.
 *
 * The blog must work identically whether typo3/cms-workspaces is installed
 * or not. TypoScript should not conditionally check for workspaces.
 */
final class TypoScriptSetupTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function typoScriptSetupFileProvider(): array
    {
        $base = self::getExtensionPath() . '/Configuration';
        $files = [];

        $dirs = [
            $base . '/Sets',
            $base . '/TypoScript',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                \assert($file instanceof \SplFileInfo);
                if ($file->getExtension() === 'typoscript') {
                    $relative = str_replace(self::getExtensionPath() . '/', '', $file->getPathname());
                    $files[$relative] = [$file->getPathname()];
                }
            }
        }

        ksort($files);
        return $files;
    }

    #[Test]
    #[DataProvider('typoScriptSetupFileProvider')]
    public function typoScriptDoesNotHardcodeWorkspaceConditions(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertDoesNotMatchRegularExpression(
            '/\[.*workspace.*\]/i',
            $content,
            'TypoScript must not contain workspace-specific conditions. '
            . 'The blog must work identically with and without workspaces.',
        );
    }

    #[Test]
    #[DataProvider('typoScriptSetupFileProvider')]
    public function typoScriptDoesNotReferenceWorkspaceExtension(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'cms-workspaces',
            $content,
            'TypoScript must not reference typo3/cms-workspaces.',
        );
    }

    #[Test]
    #[DataProvider('typoScriptSetupFileProvider')]
    public function typoScriptDoesNotManipulateRecordTransformation(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'record-transformation >',
            $content,
            'TypoScript must not unset record-transformation. '
            . 'The template fix bypasses it via .20 path instead.',
        );
    }

    #[Test]
    public function sharedSetupDefinesContentListOptions(): void
    {
        $path = self::getExtensionPath() . '/Configuration/Sets/Shared/setup.typoscript';
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringContainsString(
            'contentListOptions',
            $content,
            'Shared setup must define contentListOptions for backward compatibility '
            . 'with sitepackages that may still use the deprecated ViewHelper.',
        );
    }

    #[Test]
    public function sharedSetupDefinesAllTemplateRenderedPlugins(): void
    {
        $path = self::getExtensionPath() . '/Configuration/Sets/Shared/setup.typoscript';
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        $requiredKeys = [
            'blog_header',
            'blog_footer',
            'blog_authors',
            'blog_comments',
            'blog_commentform',
            'blog_relatedposts',
            'blog_sidebar',
        ];

        foreach ($requiredKeys as $key) {
            self::assertStringContainsString(
                $key,
                $content,
                sprintf('Shared setup must define contentListOptions for "%s".', $key),
            );
        }
    }

    #[Test]
    public function staticSetupDefinesPluginPersistenceSettings(): void
    {
        $path = self::getExtensionPath() . '/Configuration/Sets/Static/setup.typoscript';
        if (!file_exists($path)) {
            $path = self::getExtensionPath() . '/Configuration/TypoScript/Static/setup.typoscript';
        }
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringContainsString(
            'persistence',
            $content,
            'Static setup must configure Extbase persistence (storagePid).',
        );
    }
}
