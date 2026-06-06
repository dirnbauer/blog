<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Repository;

use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\Domain\Enum\CommentListFilter;
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class CommentRepository extends Repository
{
    protected array $settings = [];

    public function initializeObject(): void
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'blog');

        $querySettings = GeneralUtility::makeInstance(
            Typo3QuerySettings::class,
            GeneralUtility::makeInstance(Context::class),
            $configurationManager,
        );
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);

        $this->defaultOrderings = [
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ];
    }

    public function findAllByPost(Post $post): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];
        $constraints[] = $query->equals('post', $post->getUid());
        $constraints = $this->fillConstraintsBySettings($query, $constraints);
        $statement = $query->matching($query->logicalAnd(...$constraints));
        $result = $statement->execute();

        return $result;
    }

    public function findAllByFilter(?string $filter = null, ?int $blogSetup = null): QueryResultInterface|array
    {
        return $this->findAllByFilterAndBlogSetups($filter, $blogSetup !== null ? [$blogSetup] : null);
    }

    public function findAllByFilterAndBlogSetups(?string $filter = null, ?array $blogSetups = null): QueryResultInterface|array
    {
        $query = $this->createBlogFilterQuery($filter, $blogSetups);
        if ($query === null) {
            return [];
        }

        return $query->execute();
    }

    /**
     * @return array{all: int, pending: int, approved: int, declined: int, deleted: int}
     */
    public function countByFilterForBlogSetups(?int $blogSetup, array $blogSetupIds): array
    {
        $emptyCounts = [
            'all' => 0,
            'pending' => 0,
            'approved' => 0,
            'declined' => 0,
            'deleted' => 0,
        ];
        $blogSetups = $blogSetup !== null ? [$blogSetup] : $blogSetupIds;
        $postPids = $this->getPostPidsByRootPids($blogSetups);
        if ($postPids === []) {
            return $emptyCounts;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_blog_domain_model_comment');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->addSelectLiteral('SUM(CASE WHEN status <> ' . Comment::STATUS_DELETED . ' THEN 1 ELSE 0 END) AS all_count')
            ->addSelectLiteral('SUM(CASE WHEN status = ' . Comment::STATUS_PENDING . ' THEN 1 ELSE 0 END) AS pending_count')
            ->addSelectLiteral('SUM(CASE WHEN status = ' . Comment::STATUS_APPROVED . ' THEN 1 ELSE 0 END) AS approved_count')
            ->addSelectLiteral('SUM(CASE WHEN status = ' . Comment::STATUS_DECLINED . ' THEN 1 ELSE 0 END) AS declined_count')
            ->addSelectLiteral('SUM(CASE WHEN status = ' . Comment::STATUS_DELETED . ' THEN 1 ELSE 0 END) AS deleted_count')
            ->from('tx_blog_domain_model_comment')
            ->where($queryBuilder->expr()->in(
                'pid',
                $queryBuilder->createNamedParameter($postPids, Connection::PARAM_INT_ARRAY),
            ))
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($row)) {
            return $emptyCounts;
        }

        return [
            'all' => TypeUtility::toInt($row['all_count'] ?? 0),
            'pending' => TypeUtility::toInt($row['pending_count'] ?? 0),
            'approved' => TypeUtility::toInt($row['approved_count'] ?? 0),
            'declined' => TypeUtility::toInt($row['declined_count'] ?? 0),
            'deleted' => TypeUtility::toInt($row['deleted_count'] ?? 0),
        ];
    }

    public function countAllByFilterAndBlogSetups(?string $filter = null, ?array $blogSetups = null): int
    {
        $query = $this->createBlogFilterQuery($filter, $blogSetups);
        if ($query === null) {
            return 0;
        }

        return $query->count();
    }

    public function findActiveComments(?int $limit = null, ?int $blogSetup = null): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints = $this->fillConstraintsBySettings($query, $constraints);

        if ($limit !== null) {
            $query->setLimit($limit);
        }
        if ($blogSetup !== null) {
            $storagePids = $this->getPostPidsByRootPid($blogSetup);
            if (count($storagePids) > 0) {
                $constraints[] = $query->in('pid', $storagePids);
            }
        }

        return $query->matching($query->logicalAnd(...$constraints))->execute();
    }

    protected function getPostPidsByRootPid(int $blogRootPid): array
    {
        return $this->getPostPidsByRootPids([$blogRootPid]);
    }

    protected function getPostPidsByRootPids(array $blogRootPids): array
    {
        $blogRootPids = array_values(array_unique(array_filter(array_map('intval', $blogRootPids), static fn (int $pid): bool => $pid > 0)));
        if ($blogRootPids === []) {
            return [];
        }

        $workspaceId = GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('workspace', 'id', 0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));

        $rows = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('doktype', $queryBuilder->createNamedParameter(Constants::DOKTYPE_BLOG_POST, Connection::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($blogRootPids, Connection::PARAM_INT_ARRAY)))
            ->executeQuery()
            ->fetchAllAssociative();
        $result = [];
        foreach ($rows as $row) {
            $uid = $row['uid'] ?? 0;
            $result[] = is_numeric($uid) ? (int) $uid : 0;
        }

        return array_values(array_unique($result));
    }

    protected function createBlogFilterQuery(?string $filter, ?array $blogSetups): ?QueryInterface
    {
        $query = $this->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $query->setQuerySettings($querySettings);

        $constraints = $this->buildFilterConstraints($query, $filter);
        if (is_array($blogSetups)) {
            $postPids = $this->getPostPidsByRootPids($blogSetups);
            if ($postPids === []) {
                return null;
            }
            $constraints[] = $query->in('pid', $postPids);
        }
        if ($constraints !== []) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query;
    }

    protected function buildFilterConstraints(QueryInterface $query, ?string $filter): array
    {
        return match (CommentListFilter::tryFromRequest($filter)) {
            CommentListFilter::Pending => [$query->equals('status', Comment::STATUS_PENDING)],
            CommentListFilter::Approved => [$query->equals('status', Comment::STATUS_APPROVED)],
            CommentListFilter::Declined => [$query->equals('status', Comment::STATUS_DECLINED)],
            CommentListFilter::Deleted => [$query->equals('status', Comment::STATUS_DELETED)],
            CommentListFilter::All => [
                $query->logicalNot($query->equals('status', Comment::STATUS_DELETED)),
            ],
        };
    }

    public function fillConstraintsBySettings(QueryInterface $query, array $constraints): array
    {
        $respectCommentsModeration = isset($this->settings['comments']['moderation'])
            ? (int)$this->settings['comments']['moderation']
            : 0;
        if ($respectCommentsModeration >= 1) {
            $constraints[] = $query->equals('status', Comment::STATUS_APPROVED);
        } else {
            $constraints[] = $query->lessThan('status', Comment::STATUS_DECLINED);
        }

        $respectPostLanguageId = isset($this->settings['comments']['respectPostLanguageId'])
            ? (bool) $this->settings['comments']['respectPostLanguageId']
            : false;
        if ($respectPostLanguageId) {
            $constraints[] = $query->logicalOr(
                $query->equals('postLanguageId', GeneralUtility::makeInstance(Context::class)->getAspect('language')->getId()),
                $query->equals('postLanguageId', -1),
            );
        }

        $tstamp = time();
        $constraints[] = $query->logicalAnd(
            $query->logicalOr(
                $query->equals('post.starttime', 0),
                $query->lessThanOrEqual('post.starttime', $tstamp),
            ),
            $query->logicalOr(
                $query->equals('post.endtime', 0),
                $query->greaterThanOrEqual('post.endtime', $tstamp),
            ),
        );
        $constraints[] = $query->logicalAnd(
            $query->equals('post.hidden', 0),
            $query->equals('post.deleted', 0),
        );

        return $constraints;
    }
}
