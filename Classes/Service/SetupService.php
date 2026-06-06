<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupCreateRequest;
use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupSummary;
use T3G\AgencyPack\Blog\Utility\DataHandlerUidReplacer;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

class SetupService
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly SiteWriter $siteWriter,
        private readonly BackendAccessService $backendAccessService,
        private readonly DataHandlerUidReplacer $dataHandlerUidReplacer,
    ) {
    }

    /**
     * @return list<BlogSetupSummary>
     */
    public function determineBlogSetups(): array
    {
        $setups = [];
        $queryBuilder = $this->getQueryBuilderForTable('pages');
        $blogRootPages = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('doktype', $queryBuilder->createNamedParameter(PageRepository::DOKTYPE_SYSFOLDER, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('module', $queryBuilder->createNamedParameter('blog', Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($blogRootPages as $blogRootPage) {
            $blogUid = TypeUtility::toInt($blogRootPage['uid'] ?? null);
            $blogTitle = TypeUtility::toString($blogRootPage['title'] ?? null);
            if (!array_key_exists($blogUid, $setups)) {
                $rawRootline = GeneralUtility::makeInstance(RootlineUtility::class, $blogUid)->get();
                $rootline = [];
                foreach (array_reverse($rawRootline) as $page) {
                    if (!is_array($page)) {
                        continue;
                    }
                    $normalizedPage = [];
                    foreach ($page as $key => $value) {
                        $normalizedPage[(string)$key] = $value;
                    }
                    $rootline[] = $normalizedPage;
                }

                $queryBuilder = $this->getQueryBuilderForTable('pages');
                $articleCount = $queryBuilder
                    ->count('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('doktype', $queryBuilder->createNamedParameter(Constants::DOKTYPE_BLOG_POST, Connection::PARAM_INT)),
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($blogUid, Connection::PARAM_INT)),
                        $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    )
                    ->executeQuery()
                    ->fetchOne();

                $setups[$blogUid] = new BlogSetupSummary(
                    uid: $blogUid,
                    title: $blogTitle,
                    path: implode(' / ', array_map(static function (array $page): string {
                        return TypeUtility::toString($page['title'] ?? null);
                    }, $rootline)),
                    articleCount: TypeUtility::toInt($articleCount),
                    rootline: $rootline,
                );
            }
        }

        return $this->backendAccessService->filterAccessibleBlogSetups(array_values($setups));
    }

    public function createBlogSetup(BlogSetupCreateRequest|array $data = []): void
    {
        $request = $data instanceof BlogSetupCreateRequest
            ? $data
            : BlogSetupCreateRequest::fromRequestData($data) ?? new BlogSetupCreateRequest();
        $title = $request->title;

        $blogSetup = require GeneralUtility::getFileAbsFileName('EXT:blog/Configuration/DataHandler/BlogSetupRecords.php');
        if ($title !== null) {
            $blogSetup['pages']['NEW_blogRoot']['title'] = $title;
        }
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($blogSetup, []);
        $dataHandler->process_datamap();
        $recordUidArray = $dataHandler->substNEWwithIDs;

        $blogRootUid = TypeUtility::toInt($recordUidArray['NEW_blogRoot'] ?? null);

        $site = $this->siteFinder->getSiteByRootPageId($blogRootUid);
        $siteIdentifier = $site->getIdentifier();
        $siteConfiguration = $site->getConfiguration();
        $basicSiteConfiguration = [
            'imports' => [
                [
                    'resource' => 'EXT:blog/Configuration/Routes/Default.yaml',
                ],
            ],
            'dependencies' => [
                'blog/standalone',
            ],
        ];
        $this->siteWriter->write(
            $siteIdentifier,
            array_merge_recursive($siteConfiguration, $basicSiteConfiguration),
        );
        $this->siteWriter->writeSettings(
            $siteIdentifier,
            [
                'plugin' => [
                    'tx_blog' => [
                        'settings' => [
                            'blogUid' => TypeUtility::toInt($recordUidArray['NEW_blogRoot'] ?? null),
                            'categoryUid' => TypeUtility::toInt($recordUidArray['NEW_blogCategoryPage'] ?? null),
                            'tagUid' => TypeUtility::toInt($recordUidArray['NEW_blogTagPage'] ?? null),
                            'authorUid' => TypeUtility::toInt($recordUidArray['NEW_blogAuthorPage'] ?? null),
                            'archiveUid' => TypeUtility::toInt($recordUidArray['NEW_blogArchivePage'] ?? null),
                            'storagePid' => TypeUtility::toInt($recordUidArray['NEW_blogFolder'] ?? null),
                        ],
                    ],
                ],
            ],
        );

        $blogSetupRelations = require GeneralUtility::getFileAbsFileName('EXT:blog/Configuration/DataHandler/BlogSetupRelations.php');
        $blogSetupRelations = $this->dataHandlerUidReplacer->replace($blogSetupRelations, $recordUidArray);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($blogSetupRelations, []);
        $dataHandler->process_datamap();

        BackendUtility::setUpdateSignal('updatePageTree');
    }

    protected function getQueryBuilderForTable(string $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
    }
}
