<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * HTTP smoke tests that verify blog pages render without errors.
 *
 * These tests require a running DDEV environment. They verify:
 * - Blog pages return HTTP 200 (not 500 from IncompleteRecordException)
 * - Both blog list and blog post pages work
 * - The rendering chain does not crash with or without workspace context
 *
 * Tests are skipped when DDEV is not available (CI without DDEV).
 * To run: BLOG_DDEV_URL=https://voek2025.ddev.site vendor/bin/phpunit ...
 */
final class BlogPageHttpSmokeTest extends TestCase
{
    private static ?string $baseUrl = null;

    public static function setUpBeforeClass(): void
    {
        $url = getenv('BLOG_DDEV_URL');
        if (is_string($url) && $url !== '') {
            self::$baseUrl = rtrim($url, '/');
            return;
        }

        // Inside DDEV container: DDEV_HOSTNAME is set automatically
        $ddevHostname = getenv('DDEV_HOSTNAME');
        if (is_string($ddevHostname) && $ddevHostname !== '') {
            $primaryHost = explode(',', $ddevHostname)[0];
            self::$baseUrl = 'https://' . $primaryHost;
            return;
        }

        // Outside container: try ddev describe
        $output = shell_exec('ddev describe --json 2>/dev/null');
        if ($output !== null && is_string($output)) {
            $json = json_decode($output, true);
            if (is_array($json) && isset($json['raw']['primary_url'])) {
                self::$baseUrl = rtrim((string)$json['raw']['primary_url'], '/');
                return;
            }
        }

        self::$baseUrl = null;
    }

    private function requireDdev(): string
    {
        if (self::$baseUrl === null) {
            self::markTestSkipped('DDEV not available. Set BLOG_DDEV_URL env var to run HTTP smoke tests.');
        }
        return self::$baseUrl;
    }

    private function httpGet(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            self::fail('curl_init() failed for ' . $url);
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            self::fail('HTTP request failed: ' . $error);
        }

        return ['code' => $code, 'body' => (string)$body];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function blogPageUrlProvider(): array
    {
        return [
            'blog list page' => ['/blog1/'],
            'first blog post' => ['/blog1/first-blog-post'],
        ];
    }

    #[Test]
    #[DataProvider('blogPageUrlProvider')]
    public function blogPageReturnsHttp200(string $path): void
    {
        $base = $this->requireDdev();
        $result = $this->httpGet($base . $path);

        self::assertSame(
            200,
            $result['code'],
            sprintf(
                'Blog page %s must return HTTP 200 (got %d). '
                . 'A 500 likely indicates IncompleteRecordException from '
                . 'record-transformation with synthetic data.',
                $path,
                $result['code']
            )
        );
    }

    #[Test]
    #[DataProvider('blogPageUrlProvider')]
    public function blogPageDoesNotContainExceptionOutput(string $path): void
    {
        $base = $this->requireDdev();
        $result = $this->httpGet($base . $path);

        if ($result['code'] !== 200) {
            self::markTestSkipped('Page returned HTTP ' . $result['code']);
        }

        self::assertStringNotContainsString(
            'IncompleteRecordException',
            $result['body'],
            'Page must not output IncompleteRecordException.'
        );

        self::assertStringNotContainsString(
            'RecordPropertyNotFoundException',
            $result['body'],
            'Page must not output RecordPropertyNotFoundException.'
        );
    }

    #[Test]
    #[DataProvider('blogPageUrlProvider')]
    public function blogPageContainsHtmlStructure(string $path): void
    {
        $base = $this->requireDdev();
        $result = $this->httpGet($base . $path);

        if ($result['code'] !== 200) {
            self::markTestSkipped('Page returned HTTP ' . $result['code']);
        }

        self::assertStringContainsString(
            '</html>',
            $result['body'],
            'Page must contain complete HTML structure.'
        );

        self::assertStringContainsString(
            '</body>',
            $result['body'],
            'Page must contain closing body tag.'
        );
    }

    #[Test]
    #[DataProvider('blogPageUrlProvider')]
    public function blogPageContainsBlogContainer(string $path): void
    {
        $base = $this->requireDdev();
        $result = $this->httpGet($base . $path);

        if ($result['code'] !== 200) {
            self::markTestSkipped('Page returned HTTP ' . $result['code']);
        }

        // All blog page templates render content in a container
        self::assertMatchesRegularExpression(
            '/class="[^"]*(?:blogcontainer|container|max-w-)[^"]*"/',
            $result['body'],
            'Page must contain a blog container element.'
        );
    }

    #[Test]
    public function blogPostPageRendersSidebarPlugin(): void
    {
        $base = $this->requireDdev();
        $result = $this->httpGet($base . '/blog1/first-blog-post');

        if ($result['code'] !== 200) {
            self::markTestSkipped('Blog post page returned HTTP ' . $result['code']);
        }

        // The sidebar is rendered via tt_content.blog_sidebar.20 (EXTBASEPLUGIN).
        // Its output should appear somewhere in the page, even if empty.
        // The sidebar template wraps in a <aside> or similar element.
        self::assertMatchesRegularExpression(
            '/<aside|role="complementary"|blogcontainer-sidebar/i',
            $result['body'],
            'Blog post page must render sidebar area.'
        );
    }
}
