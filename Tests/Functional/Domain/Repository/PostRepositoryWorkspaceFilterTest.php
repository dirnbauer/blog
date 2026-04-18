<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for PostRepository workspace filtering.
 *
 * Fixtures (pages.csv + workspace_pages.csv):
 *   uid 3  – "First Blog Post"            (LIVE, t3ver_wsid=0)
 *   uid 4  – "Second Blog Post"           (LIVE, t3ver_wsid=0)
 *   uid 5  – "First Blog Post - WS Modified" (WS version of uid 3, t3ver_wsid=1)
 *   uid 6  – "New WS Blog Post"           (WS-only new, t3ver_wsid=1, t3ver_state=1)
 */
final class PostRepositoryWorkspaceFilterTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
        'workspaces',
    ];

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/blog',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $languageServiceFactory = $this->get(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($backendUser);

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/workspace_pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/sys_workspace.csv');
    }

    private function setWorkspaceId(int $workspaceId): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect($workspaceId));
        self::assertInstanceOf(BackendUserAuthentication::class, $GLOBALS['BE_USER']);
        $GLOBALS['BE_USER']->setWorkspace($workspaceId);
    }

    private function setUpBackendRequest(array $queryParams = []): void
    {
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        if ($queryParams !== []) {
            $request = $request->withQueryParams($queryParams);
        }
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * Obtain a fresh PostRepository whose initializeObject() runs with the
     * current workspace context. DI returns a shared instance, so we
     * instantiate manually for isolation between test methods.
     */
    private function createPostRepository(): PostRepository
    {
        $repository = $this->get(PostRepository::class);

        $query = $repository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $repository->setDefaultQuerySettings($querySettings);

        return $repository;
    }

    #[Test]
    public function liveWorkspaceExcludesWorkspaceOnlyPosts(): void
    {
        $this->setWorkspaceId(0);
        $this->setUpBackendRequest();

        $repository = $this->createPostRepository();
        $posts = $repository->findAllByPid();
        $postArray = $posts->toArray();
        self::assertContainsOnlyInstancesOf(Post::class, $postArray);

        $titles = array_map(
            static fn (Post $post): string => $post->getTitle(),
            $postArray,
        );

        self::assertContains('First Blog Post', $titles);
        self::assertContains('Second Blog Post', $titles);
        self::assertNotContains(
            'New WS Blog Post',
            $titles,
            'Workspace-only posts must not appear in LIVE workspace.',
        );
        self::assertNotContains(
            'First Blog Post - WS Modified',
            $titles,
            'Workspace versions of live records must not leak into LIVE.',
        );
    }

    #[Test]
    public function workspaceContextIncludesOwnWorkspacePosts(): void
    {
        $this->setWorkspaceId(1);
        $this->setUpBackendRequest();

        $repository = $this->createPostRepository();
        $posts = $repository->findAllByPid();
        $postArray = $posts->toArray();
        self::assertContainsOnlyInstancesOf(Post::class, $postArray);

        $titles = array_map(
            static fn (Post $post): string => $post->getTitle(),
            $postArray,
        );

        self::assertContains('Second Blog Post', $titles);
        self::assertContains(
            'New WS Blog Post',
            $titles,
            'Workspace-only posts must appear when the matching workspace is active.',
        );
    }

    #[Test]
    public function workspaceContextExcludesForeignWorkspacePosts(): void
    {
        // Workspace 99 does not own any of the records in workspace_pages.csv
        $this->setWorkspaceId(99);
        $this->setUpBackendRequest();

        $repository = $this->createPostRepository();
        $posts = $repository->findAllByPid();
        $postArray = $posts->toArray();
        self::assertContainsOnlyInstancesOf(Post::class, $postArray);

        $titles = array_map(
            static fn (Post $post): string => $post->getTitle(),
            $postArray,
        );

        self::assertContains('First Blog Post', $titles, 'LIVE posts must still be visible.');
        self::assertContains('Second Blog Post', $titles, 'LIVE posts must still be visible.');
        self::assertNotContains(
            'New WS Blog Post',
            $titles,
            'Posts from workspace 1 must not appear in workspace 99.',
        );
    }

    #[Test]
    public function initializeObjectSurvivesWorkspaceOnlyPageInLiveContext(): void
    {
        $this->setWorkspaceId(0);
        // Point request at uid 6 — a page that exists only in workspace 1.
        // In LIVE, the rootline cannot be resolved ⇒ PageNotFoundException.
        // The try-catch in initializeObject() must prevent a crash.
        $this->setUpBackendRequest(['id' => 6]);

        $repository = $this->get(PostRepository::class);
    }
}
