<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional rendering tests for blog pages.
 *
 * Verifies that blog pages render correctly:
 * - WITHOUT workspaces (live mode, default)
 * - WITH workspaces (workspace preview with backend user)
 *
 * The blog's renderPlugin section uses <f:cObject typoscriptObjectPath="tt_content.{listType}.20" />
 * which renders EXTBASEPLUGIN directly, bypassing the record-transformation data processor
 * that would cause IncompleteRecordException with synthetic records.
 *
 * Workspace safety is ensured because:
 * - CObjectViewHelper creates ContentObjectRenderer with PSR-7 request (carries WorkspaceAspect)
 * - EXTBASEPLUGIN forwards request to Extbase Bootstrap → Controller → Repository
 * - Repository uses Context singleton → Typo3QuerySettings → WorkspaceRestriction
 */
final class BlogPageRenderingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
        'fluid_styled_content',
        'workspaces',
    ];

    protected array $testExtensionsToLoad = [
        't3g/blog',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');

        $this->setUpFrontendRootPage(1, [
            'setup' => [
                'EXT:blog/Tests/Functional/Fixtures/TypoScript/blog_rendering.typoscript',
            ],
        ]);

        $this->writeSiteConfiguration(1);
    }

    private function writeSiteConfiguration(int $rootPageId): void
    {
        $siteDir = $this->instancePath . '/typo3conf/sites/blog-test';
        GeneralUtility::mkdir_deep($siteDir);
        $yaml = <<<YAML
rootPageId: {$rootPageId}
base: 'http://localhost/'
languages:
  -
    title: English
    enabled: true
    languageId: 0
    base: /
    locale: en_US.UTF-8
    navigationTitle: English
    flag: us
YAML;
        file_put_contents($siteDir . '/config.yaml', $yaml);
    }

    // ---------------------------------------------------------------
    // LIVE MODE (no workspaces active)
    // ---------------------------------------------------------------

    #[Test]
    public function blogListPageRendersWithoutException(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(2)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Blog list page (doktype 138) must return HTTP 200 in live mode.'
        );
    }

    #[Test]
    public function blogPostPageRendersWithoutException(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Blog post page (doktype 137) must return HTTP 200 in live mode.'
        );
    }

    #[Test]
    public function blogListPageContainsHtmlOutput(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(2)
        );

        $body = (string)$response->getBody();
        self::assertStringContainsString('</html>', $body, 'Blog list page must produce valid HTML.');
    }

    #[Test]
    public function blogPostPageContainsHtmlOutput(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3)
        );

        $body = (string)$response->getBody();
        self::assertStringContainsString('</html>', $body, 'Blog post page must produce valid HTML.');
    }

    #[Test]
    public function blogPostPageDoesNotContainIncompleteRecordException(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3)
        );

        $body = (string)$response->getBody();
        self::assertStringNotContainsString(
            'IncompleteRecordException',
            $body,
            'Blog post must NOT throw IncompleteRecordException in live mode.'
        );
    }

    #[Test]
    public function blogListPageDoesNotContainIncompleteRecordException(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(2)
        );

        $body = (string)$response->getBody();
        self::assertStringNotContainsString(
            'IncompleteRecordException',
            $body,
            'Blog list must NOT throw IncompleteRecordException in live mode.'
        );
    }

    #[Test]
    public function secondBlogPostPageRendersInLiveMode(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(4)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Second blog post page must return HTTP 200 in live mode.'
        );
    }

    #[Test]
    public function blogPostPageDoesNotContainRecordPropertyNotFoundException(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3)
        );

        $body = (string)$response->getBody();
        self::assertStringNotContainsString(
            'RecordPropertyNotFoundException',
            $body,
            'Blog post must NOT throw RecordPropertyNotFoundException.'
        );
    }

    // ---------------------------------------------------------------
    // WORKSPACE MODE (workspace preview with backend user)
    // ---------------------------------------------------------------

    #[Test]
    public function blogListPageRendersInWorkspacePreview(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/workspace_pages.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(2),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(1)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Blog list page must return HTTP 200 in workspace preview.'
        );
    }

    #[Test]
    public function blogPostPageRendersInWorkspacePreview(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/workspace_pages.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(1)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Blog post page must return HTTP 200 in workspace preview.'
        );
    }

    #[Test]
    public function blogPostPageDoesNotContainExceptionInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/workspace_pages.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(1)
        );

        $body = (string)$response->getBody();
        self::assertStringNotContainsString(
            'IncompleteRecordException',
            $body,
            'Blog post must NOT throw IncompleteRecordException in workspace mode.'
        );
        self::assertStringNotContainsString(
            'RecordPropertyNotFoundException',
            $body,
            'Blog post must NOT throw RecordPropertyNotFoundException in workspace mode.'
        );
    }

    #[Test]
    public function blogListPageDoesNotContainExceptionInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/workspace_pages.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(2),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(1)
        );

        $body = (string)$response->getBody();
        self::assertStringNotContainsString(
            'IncompleteRecordException',
            $body,
            'Blog list must NOT throw IncompleteRecordException in workspace mode.'
        );
    }

    #[Test]
    public function blogPostPageContainsHtmlOutputInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/workspace_pages.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(1)
        );

        $body = (string)$response->getBody();
        self::assertStringContainsString(
            '</html>',
            $body,
            'Blog post page must produce valid HTML in workspace preview.'
        );
    }

    #[Test]
    public function unmodifiedBlogPostRendersInWorkspacePreview(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/workspace_pages.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(4),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(1)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Unmodified blog post must also render HTTP 200 in workspace preview.'
        );
    }

    // ---------------------------------------------------------------
    // WORKSPACE OFF (workspaces loaded but no workspace selected)
    // ---------------------------------------------------------------

    #[Test]
    public function blogPostPageRendersWithBackendUserButNoWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(3),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(0)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Blog post must render HTTP 200 with backend user in live workspace (id=0).'
        );
    }

    #[Test]
    public function blogListPageRendersWithBackendUserButNoWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withPageId(2),
            (new InternalRequestContext())
                ->withBackendUserId(1)
                ->withWorkspaceId(0)
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Blog list must render HTTP 200 with backend user in live workspace (id=0).'
        );
    }
}
