<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\Service\SetupService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SetupServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
        'fluid_styled_content',
    ];

    protected array $testExtensionsToLoad = [
        'blog',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DataHandler/be_users.csv');
        $this->setUpLanguageForBackendUser(1);
    }

    protected function setUpLanguageForBackendUser(int $backendUserUid): void
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $languageServiceFactory = $this->get(LanguageServiceFactory::class);
        self::assertInstanceOf(LanguageServiceFactory::class, $languageServiceFactory);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($backendUser);
    }

    protected function createRestrictedBackendUser(int $backendUserUid, int $mountPoint): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->insert('be_users', [
                'uid' => $backendUserUid,
                'pid' => 0,
                'username' => 'editor-' . $backendUserUid,
                'password' => '$1$tCrlLajZ$C0sikFQQ3SWaFAZ1Me0Z/1',
                'admin' => 0,
                'disable' => 0,
                'deleted' => 0,
                'options' => 0,
                'crdate' => 0,
                'tstamp' => 0,
                'workspace_perms' => 1,
                'workspace_id' => 0,
                'db_mountpoints' => (string)$mountPoint,
                'uc' => '',
            ]);
    }

    #[Test]
    public function create(): void
    {
        $setupService = GeneralUtility::makeInstance(SetupService::class);
        $setupService->createBlogSetup();

        /** @var array $rootPage */
        $rootPage = BackendUtility::getRecord('pages', 1);
        self::assertEquals($rootPage['title'], 'Blog');
        self::assertEquals($rootPage['doktype'], Constants::DOKTYPE_BLOG_PAGE);
        self::assertEquals($rootPage['is_siteroot'], 1);
        self::assertEquals($rootPage['backend_layout'], 'pagets__BlogList');
        /** @var array $storagePage */
        $storagePage = BackendUtility::getRecord('pages', 2);
        self::assertEquals($storagePage['backend_layout_next_level'], 'pagets__BlogPost');
    }

    #[Test]
    public function createWithName(): void
    {
        $setupService = GeneralUtility::makeInstance(SetupService::class);
        $setupService->createBlogSetup(['title' => 'DEMO']);

        /** @var array $rootPage */
        $rootPage = BackendUtility::getRecord('pages', 1);
        self::assertEquals($rootPage['title'], 'DEMO');
        self::assertEquals($rootPage['doktype'], Constants::DOKTYPE_BLOG_PAGE);
        self::assertEquals($rootPage['is_siteroot'], 1);
        self::assertEquals($rootPage['backend_layout'], 'pagets__BlogList');
        /** @var array $storagePage */
        $storagePage = BackendUtility::getRecord('pages', 2);
        self::assertEquals($storagePage['backend_layout_next_level'], 'pagets__BlogPost');
    }

    #[Test]
    public function determineBlogSetups(): void
    {
        $setupService = GeneralUtility::makeInstance(SetupService::class);
        $setupService->createBlogSetup(['title' => 'TEST 1']);
        $setupService->createBlogSetup(['title' => 'TEST 2']);

        $blogSetups = $setupService->determineBlogSetups();

        $blogSetup1 = array_shift($blogSetups);
        self::assertEquals($blogSetup1['path'], 'TEST 1 / Data');
        $blogSetup2 = array_shift($blogSetups);
        self::assertEquals($blogSetup2['path'], 'TEST 2 / Data');
    }

    #[Test]
    public function determineBlogSetupsRespectsBackendUserMounts(): void
    {
        $setupService = GeneralUtility::makeInstance(SetupService::class);
        $setupService->createBlogSetup(['title' => 'TEST 1']);
        $setupService->createBlogSetup(['title' => 'TEST 2']);

        $blogSetups = array_values($setupService->determineBlogSetups());
        $firstSetup = $blogSetups[0];
        $this->createRestrictedBackendUser(2, (int)$firstSetup['uid']);
        $this->setUpLanguageForBackendUser(2);

        $restrictedBlogSetups = array_values($setupService->determineBlogSetups());

        self::assertCount(1, $restrictedBlogSetups);
        self::assertSame((int)$firstSetup['uid'], (int)$restrictedBlogSetups[0]['uid']);
        self::assertSame($firstSetup['path'], $restrictedBlogSetups[0]['path']);
    }
}
