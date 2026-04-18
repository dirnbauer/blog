<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Blog\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerHookWorkspaceTest extends FunctionalTestCase
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

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataHandler/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $languageServiceFactory = $this->get(LanguageServiceFactory::class);
        self::assertInstanceOf(LanguageServiceFactory::class, $languageServiceFactory);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($backendUser);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/BlogBasePages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceTestData.csv');
    }

    private function setWorkspaceId(int $workspaceId): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect($workspaceId));
        self::assertInstanceOf(BackendUserAuthentication::class, $GLOBALS['BE_USER']);
        $GLOBALS['BE_USER']->setWorkspace($workspaceId);
    }

    #[Test]
    public function workspaceVersionGetsPublishDateSet(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_post' => [
                    'pid' => 2,
                    'hidden' => 0,
                    'title' => 'Live Post',
                    'doktype' => Constants::DOKTYPE_BLOG_POST,
                    'publish_date' => 1689811200,
                    'crdate_month' => 0,
                    'crdate_year' => 0,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $liveRecord = BackendUtility::getRecord('pages', 3);
        self::assertIsArray($liveRecord);
        self::assertSame(7, (int)$liveRecord['crdate_month']);
        self::assertSame(2023, (int)$liveRecord['crdate_year']);

        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                3 => [
                    'title' => 'Modified In Workspace',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $this->setWorkspaceId(0);
        $liveRecordAfter = BackendUtility::getRecord('pages', 3);
        self::assertIsArray($liveRecordAfter);
        self::assertSame('Live Post', $liveRecordAfter['title']);
        self::assertSame(7, (int)$liveRecordAfter['crdate_month']);
    }

    #[Test]
    public function cacheIsNotFlushedInWorkspaceContext(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_post' => [
                    'pid' => 2,
                    'hidden' => 0,
                    'title' => 'Test Post',
                    'doktype' => Constants::DOKTYPE_BLOG_POST,
                    'publish_date' => 1689811200,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                3 => [
                    'title' => 'Workspace Edit',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        self::assertEmpty($dataHandler->errorLog, 'DataHandler errors: ' . implode(', ', $dataHandler->errorLog));
    }

    #[Test]
    public function workspacePlaceholderIsSkipped(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_post' => [
                    'pid' => 2,
                    'hidden' => 0,
                    'title' => 'Original Post',
                    'doktype' => Constants::DOKTYPE_BLOG_POST,
                    'publish_date' => 1689811200,
                    'crdate_month' => 0,
                    'crdate_year' => 0,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $liveRecord = BackendUtility::getRecord('pages', 3);
        self::assertIsArray($liveRecord);
        self::assertSame(7, (int)$liveRecord['crdate_month']);

        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                3 => [
                    'publish_date' => 1653004800,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $this->setWorkspaceId(0);
        $liveRecordUnchanged = BackendUtility::getRecord('pages', 3);
        self::assertIsArray($liveRecordUnchanged);
        self::assertSame(7, (int)$liveRecordUnchanged['crdate_month']);
        self::assertSame(2023, (int)$liveRecordUnchanged['crdate_year']);
    }

    #[Test]
    public function tagRecordIsVersionedInWorkspace(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blog_domain_model_tag' => [
                'NEW_tag' => [
                    'pid' => 2,
                    'title' => 'Live Tag',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        $tagUid = $dataHandler->substNEWwithIDs['NEW_tag'];
        self::assertGreaterThan(0, $tagUid);

        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blog_domain_model_tag' => [
                $tagUid => [
                    'title' => 'Workspace Tag',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        self::assertEmpty($dataHandler->errorLog, 'DataHandler errors: ' . implode(', ', $dataHandler->errorLog));

        $this->setWorkspaceId(0);
        $liveTag = BackendUtility::getRecord('tx_blog_domain_model_tag', $tagUid);
        self::assertIsArray($liveTag);
        self::assertSame('Live Tag', $liveTag['title']);

        $this->setWorkspaceId(1);
        $wsTag = BackendUtility::getRecordWSOL('tx_blog_domain_model_tag', $tagUid);
        self::assertIsArray($wsTag);
        self::assertSame('Workspace Tag', $wsTag['title']);
    }

    #[Test]
    public function authorRecordIsVersionedInWorkspace(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blog_domain_model_author' => [
                'NEW_author' => [
                    'pid' => 2,
                    'name' => 'Live Author',
                    'email' => 'author@example.com',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        $authorUid = $dataHandler->substNEWwithIDs['NEW_author'];
        self::assertGreaterThan(0, $authorUid);

        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blog_domain_model_author' => [
                $authorUid => [
                    'name' => 'Workspace Author',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        self::assertEmpty($dataHandler->errorLog, 'DataHandler errors: ' . implode(', ', $dataHandler->errorLog));

        $this->setWorkspaceId(0);
        $liveAuthor = BackendUtility::getRecord('tx_blog_domain_model_author', $authorUid);
        self::assertIsArray($liveAuthor);
        self::assertSame('Live Author', $liveAuthor['name']);

        $this->setWorkspaceId(1);
        $wsAuthor = BackendUtility::getRecordWSOL('tx_blog_domain_model_author', $authorUid);
        self::assertIsArray($wsAuthor);
        self::assertSame('Workspace Author', $wsAuthor['name']);
    }

    #[Test]
    public function commentRecordIsLiveEditableInWorkspace(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_post' => [
                    'pid' => 2,
                    'hidden' => 0,
                    'title' => 'Post With Comment',
                    'doktype' => Constants::DOKTYPE_BLOG_POST,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blog_domain_model_comment' => [
                'NEW_comment' => [
                    'pid' => 3,
                    'name' => 'Commenter',
                    'email' => 'test@example.com',
                    'comment' => 'Original comment',
                    'status' => 0,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        $commentUid = $dataHandler->substNEWwithIDs['NEW_comment'];

        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_blog_domain_model_comment' => [
                $commentUid => [
                    'status' => 10,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        self::assertEmpty($dataHandler->errorLog, 'DataHandler errors: ' . implode(', ', $dataHandler->errorLog));

        $this->setWorkspaceId(0);
        $comment = BackendUtility::getRecord('tx_blog_domain_model_comment', $commentUid);
        self::assertIsArray($comment);
        self::assertSame(10, (int)$comment['status'], 'Comment should be live-edited even in workspace context');
    }

    #[Test]
    public function newPostInWorkspaceIsNotVisibleInLive(): void
    {
        $this->setWorkspaceId(1);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW_ws_post' => [
                    'pid' => 2,
                    'hidden' => 0,
                    'title' => 'Workspace-Only Post',
                    'doktype' => Constants::DOKTYPE_BLOG_POST,
                    'publish_date' => 1689811200,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        self::assertEmpty($dataHandler->errorLog, 'DataHandler errors: ' . implode(', ', $dataHandler->errorLog));

        $this->setWorkspaceId(0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $liveRecords = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('doktype', $queryBuilder->createNamedParameter(Constants::DOKTYPE_BLOG_POST, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $titles = array_column($liveRecords, 'title');
        self::assertNotContains('Workspace-Only Post', $titles);
    }
}
