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
 * Verify that TCA definitions for blog domain models have correct workspace
 * configuration (versioningWS) so that:
 * - Tags and Authors can be versioned in workspaces
 * - Comments remain live-editable (versioningWS_alwaysAllowLiveEdit)
 * - All workspace-aware tables define the required fields
 *
 * This ensures the blog works WITH workspaces (versioned data) and WITHOUT
 * workspaces (no workspace overlay applied, live data shown).
 */
final class TcaWorkspaceConfigurationTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function loadTcaFile(string $relativePath): string
    {
        $path = self::getExtensionPath() . '/Configuration/TCA/' . $relativePath;
        self::assertFileExists($path, 'TCA file must exist: ' . $relativePath);
        $content = file_get_contents($path);
        self::assertNotFalse($content);
        return $content;
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function blogTableWorkspaceConfigProvider(): array
    {
        return [
            'tags are workspace-aware' => ['tx_blog_domain_model_tag.php', true],
            'authors are workspace-aware' => ['tx_blog_domain_model_author.php', true],
            'comments allow live editing' => ['tx_blog_domain_model_comment.php', false],
        ];
    }

    #[Test]
    #[DataProvider('blogTableWorkspaceConfigProvider')]
    public function tcaHasCorrectWorkspaceConfiguration(string $tcaFile, bool $expectVersioningWS): void
    {
        $content = self::loadTcaFile($tcaFile);

        if ($expectVersioningWS) {
            self::assertMatchesRegularExpression(
                "/'versioningWS'\s*=>\s*true/",
                $content,
                $tcaFile . ' must have versioningWS => true for workspace support.',
            );
        } else {
            self::assertMatchesRegularExpression(
                "/'versioningWS_alwaysAllowLiveEdit'\s*=>\s*true/",
                $content,
                $tcaFile . ' must have versioningWS_alwaysAllowLiveEdit => true (comments are live-editable).',
            );
        }
    }

    #[Test]
    public function tagsTcaHasLanguageFields(): void
    {
        $content = self::loadTcaFile('tx_blog_domain_model_tag.php');

        self::assertStringContainsString("'languageField'", $content, 'Tags TCA must define languageField.');
        self::assertStringContainsString("'transOrigPointerField'", $content, 'Tags TCA must define transOrigPointerField.');
    }

    #[Test]
    public function authorsTcaHasDeleteField(): void
    {
        $content = self::loadTcaFile('tx_blog_domain_model_author.php');
        self::assertStringContainsString("'delete'", $content, 'Authors TCA must define delete field.');
    }

    #[Test]
    public function commentsTcaHasDeleteAndHiddenFields(): void
    {
        $content = self::loadTcaFile('tx_blog_domain_model_comment.php');
        self::assertStringContainsString("'delete'", $content, 'Comments TCA must define delete field.');
        self::assertStringContainsString("'disabled'", $content, 'Comments TCA must define hidden/disabled field.');
    }

    #[Test]
    public function tagsTcaHasTimestampFields(): void
    {
        $content = self::loadTcaFile('tx_blog_domain_model_tag.php');
        self::assertStringContainsString("'tstamp'", $content, 'Tags TCA must define tstamp.');
        self::assertStringContainsString("'crdate'", $content, 'Tags TCA must define crdate.');
    }

    #[Test]
    public function authorsTcaHasTimestampFields(): void
    {
        $content = self::loadTcaFile('tx_blog_domain_model_author.php');
        self::assertStringContainsString("'tstamp'", $content, 'Authors TCA must define tstamp.');
        self::assertStringContainsString("'crdate'", $content, 'Authors TCA must define crdate.');
    }

    #[Test]
    public function commentsTcaHasTimestampFields(): void
    {
        $content = self::loadTcaFile('tx_blog_domain_model_comment.php');
        self::assertStringContainsString("'tstamp'", $content, 'Comments TCA must define tstamp.');
        self::assertStringContainsString("'crdate'", $content, 'Comments TCA must define crdate.');
    }

    #[Test]
    public function allTcaFilesExist(): void
    {
        $tcaFiles = [
            'tx_blog_domain_model_tag.php',
            'tx_blog_domain_model_comment.php',
            'tx_blog_domain_model_author.php',
        ];

        foreach ($tcaFiles as $file) {
            self::assertFileExists(
                self::getExtensionPath() . '/Configuration/TCA/' . $file,
                'TCA file must exist: ' . $file,
            );
        }
    }

    #[Test]
    public function tcaOverridesExistForPagesAndTtContent(): void
    {
        $overridesDir = self::getExtensionPath() . '/Configuration/TCA/Overrides';
        self::assertFileExists($overridesDir . '/pages.php', 'TCA override for pages must exist.');
        self::assertFileExists($overridesDir . '/tt_content.php', 'TCA override for tt_content must exist.');
    }
}
