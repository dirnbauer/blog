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
 * Regression test: blog page templates must NOT render synthetic tt_content
 * records through the standard `tt_content` TypoScript CASE object.
 *
 * In TYPO3 v14, `lib.contentElement` includes the `record-transformation`
 * data processor which requires ALL system fields (sys_language_uid,
 * l18n_parent, t3ver_*, crdate, tstamp, hidden, header, …).
 * The blog extension creates synthetic records via ContentListOptionsViewHelper
 * that lack these fields, causing IncompleteRecordException.
 *
 * The fix renders Extbase plugins directly via `tt_content.{listType}.20`,
 * bypassing the full content element pipeline.
 *
 * Workspace safety: the `.20` path renders an EXTBASEPLUGIN content object.
 * CObjectViewHelper creates a fresh ContentObjectRenderer and sets the
 * current PSR-7 request on it (which carries WorkspaceAspect). The Extbase
 * Bootstrap forwards this request to repositories, so workspace overlays
 * are applied automatically via Context → Typo3QuerySettings. Passing
 * synthetic data that lacks t3ver_wsid / t3ver_oid / t3ver_state would
 * break this chain because ContentObjectRenderer::start() would replace
 * the workspace-aware record data with incomplete synthetic data.
 *
 * @see https://github.com/dirnbauer/blog
 */
final class BlogPageTemplateRegressionTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function blogPageTemplateProvider(): array
    {
        $base = self::getExtensionPath() . '/Resources/Private/Templates';
        $templates = [];

        $paths = [
            'Page/BlogList' => $base . '/Page/BlogList.html',
            'Page/BlogPost' => $base . '/Page/BlogPost.html',
            'ModernTailwind/Page/BlogList' => $base . '/ModernTailwind/Page/BlogList.html',
            'ModernTailwind/Page/BlogPost' => $base . '/ModernTailwind/Page/BlogPost.html',
            'ModernBootstrap/Page/BlogList' => $base . '/ModernBootstrap/Page/BlogList.html',
            'ModernBootstrap/Page/BlogPost' => $base . '/ModernBootstrap/Page/BlogPost.html',
        ];

        foreach ($paths as $label => $path) {
            if (file_exists($path)) {
                $templates[$label] = [$path];
            }
        }

        return $templates;
    }

    #[Test]
    #[DataProvider('blogPageTemplateProvider')]
    public function templateDoesNotRenderSyntheticContentThroughTtContentCase(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        // The old broken pattern passed synthetic data through the full
        // tt_content CASE rendering pipeline which includes record-transformation:
        //   {blogvh:data.contentListOptions(listType: listType)}
        //   <f:cObject typoscriptObjectPath="tt_content" data="{contentObjectData}" table="tt_content"/>
        self::assertStringNotContainsString(
            'contentListOptions',
            $content,
            'Template must not use ContentListOptionsViewHelper — synthetic records '
            . 'break record-transformation in TYPO3 v14. '
            . 'Use <f:cObject typoscriptObjectPath="tt_content.{listType}.20" /> instead.'
        );
    }

    #[Test]
    #[DataProvider('blogPageTemplateProvider')]
    public function templateDoesNotPassSyntheticDataAsTtContent(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        // Detect the pattern: data="{contentObjectData}" table="tt_content"
        // This forces synthetic data through the record-transformation pipeline.
        self::assertDoesNotMatchRegularExpression(
            '/data="\{contentObjectData\}".*table="tt_content"/s',
            $content,
            'Template must not pass contentObjectData as tt_content table — '
            . 'record-transformation requires complete database rows.'
        );
    }

    #[Test]
    #[DataProvider('blogPageTemplateProvider')]
    public function renderPluginSectionUsesDirectExtbasePluginPath(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        if (!str_contains($content, 'renderPlugin')) {
            self::markTestSkipped('Template does not contain renderPlugin section');
        }

        self::assertMatchesRegularExpression(
            '/typoscriptObjectPath="tt_content\.\{listType\}\.20"/',
            $content,
            'renderPlugin section must render Extbase plugins directly via '
            . 'tt_content.{listType}.20 to avoid record-transformation.'
        );
    }

    #[Test]
    #[DataProvider('blogPageTemplateProvider')]
    public function renderPluginDoesNotPassDataAttributeThatOverridesWorkspaceContext(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        if (!str_contains($content, 'renderPlugin')) {
            self::markTestSkipped('Template does not contain renderPlugin section');
        }

        $sectionPattern = '/<f:section name="renderPlugin">(.*?)<\/f:section>/s';
        if (!preg_match($sectionPattern, $content, $matches)) {
            self::markTestSkipped('Could not extract renderPlugin section');
        }
        $sectionContent = $matches[1];

        // When <f:cObject> is called WITHOUT a data attribute, the ViewHelper
        // creates a fresh ContentObjectRenderer that inherits the PSR-7 request
        // (including WorkspaceAspect). Passing data="..." would call
        // ContentObjectRenderer::start($data, $table), replacing the record
        // context with potentially incomplete data that lacks t3ver_* fields.
        self::assertDoesNotMatchRegularExpression(
            '/<f:cObject[^>]+\bdata\s*=/',
            $sectionContent,
            'renderPlugin must NOT pass a data attribute to <f:cObject>. '
            . 'Synthetic data lacks t3ver_wsid/t3ver_oid/t3ver_state and would '
            . 'break workspace overlay on the ContentObjectRenderer.'
        );
    }

    #[Test]
    #[DataProvider('blogPageTemplateProvider')]
    public function renderPluginDoesNotPassTableAttributeThatTriggersRecordTransformation(string $templatePath): void
    {
        $content = file_get_contents($templatePath);
        self::assertNotFalse($content, 'Template file must be readable: ' . $templatePath);

        if (!str_contains($content, 'renderPlugin')) {
            self::markTestSkipped('Template does not contain renderPlugin section');
        }

        $sectionPattern = '/<f:section name="renderPlugin">(.*?)<\/f:section>/s';
        if (!preg_match($sectionPattern, $content, $matches)) {
            self::markTestSkipped('Could not extract renderPlugin section');
        }
        $sectionContent = $matches[1];

        // table="tt_content" on <f:cObject> tells TYPO3 to treat the data as
        // a tt_content row. Combined with the full rendering pipeline this
        // triggers record-transformation which validates workspace fields.
        // The .20 path renders EXTBASEPLUGIN directly and must not declare a table.
        self::assertDoesNotMatchRegularExpression(
            '/<f:cObject[^>]+\btable\s*=\s*"tt_content"/',
            $sectionContent,
            'renderPlugin must NOT set table="tt_content" on <f:cObject>. '
            . 'This triggers workspace-field validation on synthetic data.'
        );
    }

    #[Test]
    public function extbasePluginTypoScriptPathExistsForAllBlogCTypes(): void
    {
        $extLocalconf = self::getExtensionPath() . '/ext_localconf.php';
        self::assertFileExists($extLocalconf, 'ext_localconf.php must exist');

        $content = file_get_contents($extLocalconf);
        self::assertNotFalse($content);

        // These are the CTypes rendered directly in blog page templates.
        // Each must be registered via ExtensionUtility::configurePlugin()
        // which creates the tt_content.{CType}.20 = EXTBASEPLUGIN definition.
        $requiredPlugins = [
            'blog_header' => 'Header',
            'blog_footer' => 'Footer',
            'blog_authors' => 'Authors',
            'blog_comments' => 'Comments',
            'blog_commentform' => 'CommentForm',
            'blog_relatedposts' => 'RelatedPosts',
            'blog_sidebar' => 'Sidebar',
        ];

        foreach ($requiredPlugins as $ctype => $pluginName) {
            self::assertStringContainsString(
                "'" . $pluginName . "'",
                $content,
                sprintf(
                    'Plugin "%s" (CType: %s) must be registered in ext_localconf.php '
                    . 'via ExtensionUtility::configurePlugin() so that '
                    . 'tt_content.%s.20 (EXTBASEPLUGIN) exists for workspace-safe rendering.',
                    $pluginName,
                    $ctype,
                    $ctype
                )
            );
        }
    }
}
