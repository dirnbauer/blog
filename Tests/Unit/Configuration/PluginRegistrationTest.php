<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verify that every blog CType used in page templates is properly registered
 * via ExtensionUtility::configurePlugin() in ext_localconf.php.
 *
 * This is critical because the templates render plugins via
 * tt_content.{listType}.20 — if a plugin is not registered, the .20
 * TypoScript path won't exist and the blog page will silently fail
 * in both live and workspace modes.
 */
final class PluginRegistrationTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function getExtLocalconfContent(): string
    {
        $content = file_get_contents(self::getExtensionPath() . '/ext_localconf.php');
        self::assertNotFalse($content);
        return $content;
    }

    /**
     * CTypes rendered directly in blog page templates via renderPlugin section.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function templateRenderedPluginProvider(): array
    {
        return [
            'blog_header' => ['blog_header', 'Header'],
            'blog_footer' => ['blog_footer', 'Footer'],
            'blog_authors' => ['blog_authors', 'Authors'],
            'blog_comments' => ['blog_comments', 'Comments'],
            'blog_commentform' => ['blog_commentform', 'CommentForm'],
            'blog_relatedposts' => ['blog_relatedposts', 'RelatedPosts'],
            'blog_sidebar' => ['blog_sidebar', 'Sidebar'],
        ];
    }

    /**
     * All plugins registered in the blog extension.
     *
     * @return array<string, array{0: string}>
     */
    public static function allRegisteredPluginProvider(): array
    {
        return [
            'Posts' => ['Posts'],
            'DemandedPosts' => ['DemandedPosts'],
            'LatestPosts' => ['LatestPosts'],
            'Category' => ['Category'],
            'AuthorPosts' => ['AuthorPosts'],
            'Tag' => ['Tag'],
            'Archive' => ['Archive'],
            'Sidebar' => ['Sidebar'],
            'CommentForm' => ['CommentForm'],
            'Comments' => ['Comments'],
            'Header' => ['Header'],
            'Footer' => ['Footer'],
            'Authors' => ['Authors'],
            'RelatedPosts' => ['RelatedPosts'],
            'RecentPostsWidget' => ['RecentPostsWidget'],
            'CategoryWidget' => ['CategoryWidget'],
            'TagWidget' => ['TagWidget'],
            'CommentsWidget' => ['CommentsWidget'],
            'ArchiveWidget' => ['ArchiveWidget'],
            'FeedWidget' => ['FeedWidget'],
        ];
    }

    #[Test]
    #[DataProvider('templateRenderedPluginProvider')]
    public function pluginUsedInTemplateIsRegistered(string $ctype, string $pluginName): void
    {
        $content = self::getExtLocalconfContent();

        self::assertStringContainsString(
            "'" . $pluginName . "'",
            $content,
            sprintf(
                'Plugin "%s" (CType: %s) must be registered in ext_localconf.php. '
                . 'Without it, tt_content.%s.20 won\'t exist and the blog page will '
                . 'fail in both live and workspace modes.',
                $pluginName,
                $ctype,
                $ctype
            )
        );
    }

    #[Test]
    #[DataProvider('allRegisteredPluginProvider')]
    public function pluginIsRegisteredAsContentElement(string $pluginName): void
    {
        $content = self::getExtLocalconfContent();

        self::assertStringContainsString(
            'PLUGIN_TYPE_CONTENT_ELEMENT',
            $content,
            'All blog plugins must be registered with PLUGIN_TYPE_CONTENT_ELEMENT.'
        );
    }

    #[Test]
    public function extLocalconfExists(): void
    {
        self::assertFileExists(
            self::getExtensionPath() . '/ext_localconf.php',
            'ext_localconf.php must exist for plugin registration.'
        );
    }

    #[Test]
    public function extLocalconfRegistersFluidNamespace(): void
    {
        $content = self::getExtLocalconfContent();

        self::assertStringContainsString(
            "['SYS']['fluid']['namespaces']['blogvh']",
            $content,
            'The "blogvh" Fluid namespace must be registered for the extension ViewHelpers.'
        );
    }
}
