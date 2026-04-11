<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Repository;

use T3G\AgencyPack\Blog\Domain\Model\Tag;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Tag>
 */
class TagRepository extends Repository
{
    protected array $settings = [];

    public function initializeObject(): void
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'blog');

        $this->defaultOrderings = [
            'title' => QueryInterface::ORDER_ASCENDING,
        ];
    }

    public function findByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->in('uid', $uids)
        );

        return $query->execute();
    }

    public function findTopByUsage(int $limit = 20): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_blog_domain_model_tag');
        $queryBuilder
            ->select('t.uid', 't.title')
            ->addSelectLiteral($queryBuilder->expr()->count('mm.uid_foreign', 'cnt'))
            ->from('tx_blog_domain_model_tag', 't')
            ->join('t', 'tx_blog_tag_pages_mm', 'mm', 'mm.uid_foreign = t.uid')
            ->groupBy('t.title', 't.uid')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit);

        $storagePidSetting = TypeUtility::toString($this->settings['persistence']['storagePid'] ?? '');
        if ($storagePidSetting !== '') {
            $storagePids = GeneralUtility::intExplode(',', $storagePidSetting, true);
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    't.pid',
                    $queryBuilder->createNamedParameter($storagePids, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $result = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        $rows = [];
        foreach ($result as $row) {
            $uid = TypeUtility::toInt($row['uid'] ?? null);
            if ($uid <= 0) {
                continue;
            }

            $row['tagObject'] = $this->findByUid($uid);
            $rows[] = $row;
        }

        shuffle($rows);
        return $rows;
    }
}
