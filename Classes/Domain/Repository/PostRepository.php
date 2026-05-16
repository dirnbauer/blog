<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Repository;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\DataTransferObject\PostRepositoryDemand;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Domain\Model\Category;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Model\Tag;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Post>
 */
class PostRepository extends Repository
{
    protected array $settings = [];
    protected array $defaultConstraints = [];

    public function initializeObject(): void
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $this->settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'blog');

        $querySettings = GeneralUtility::makeInstance(
            Typo3QuerySettings::class,
            GeneralUtility::makeInstance(Context::class),
            $configurationManager,
        );
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);

        // createQuery() internally resolves TypoScript through
        // BackendConfigurationManager which requires a valid rootline.
        // Workspace-only pages (t3ver_wsid>0, t3ver_oid=0) have no live
        // counterpart, so rootline resolution throws PageNotFoundException
        // when the editor is in LIVE context.  In that case we skip the
        // default constraints — the repository stays instantiable so the
        // DI container does not crash on the Page Layout view.
        try {
            $context = GeneralUtility::makeInstance(Context::class);
            $query = $this->createQuery();
            $this->defaultConstraints[] = $query->equals('doktype', Constants::DOKTYPE_BLOG_POST);
            if ($context->getAspect('language')->getId() === 0) {
                $this->defaultConstraints[] = $query->logicalOr(
                    $query->equals('l18n_cfg', 0),
                    $query->equals('l18n_cfg', 2),
                );
            } else {
                $this->defaultConstraints[] = $query->lessThan('l18n_cfg', 2);
            }

            if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            ) {
                $workspaceId = TypeUtility::toInt($context->getPropertyFromAspect('workspace', 'id', 0));
                if ($workspaceId === 0) {
                    $this->defaultConstraints[] = $query->equals('t3ver_wsid', 0);
                } else {
                    $this->defaultConstraints[] = $query->logicalOr(
                        $query->equals('t3ver_wsid', 0),
                        $query->equals('t3ver_wsid', $workspaceId),
                    );
                }
            }
        } catch (PageNotFoundException) {
            // Constraint setup failed — see comment above.
        }

        $this->defaultOrderings = [
            'publish_date' => QueryInterface::ORDER_DESCENDING,
        ];
    }

    public function findByUidRespectQuerySettings(int $uid): ?Post
    {
        $query = $this->createQuery();
        $query->matching($query->equals('uid', $uid));
        /** @var null|Post */
        $result = $query->execute()->getFirst();

        return $result;
    }

    /**
     * @return Post[]
     */
    public function findByRepositoryDemand(PostRepositoryDemand $repositoryDemand): array
    {
        $query = $this->createQuery();

        $constraints = [
            $query->equals('doktype', Constants::DOKTYPE_BLOG_POST),
        ];

        if ($repositoryDemand->getPosts() !== []) {
            $constraints[] = $query->in('uid', $repositoryDemand->getPosts());
        } else {
            if ($repositoryDemand->getCategories() !== []) {
                $categoriesConstraints = [];
                foreach ($repositoryDemand->getCategories() as $category) {
                    $categoriesConstraints[] = $query->equals('categories.uid', $category->getUid());
                }
                if ($repositoryDemand->getCategoriesConjunction() === Constants::REPOSITORY_CONJUNCTION_AND) {
                    $constraints[] = $query->logicalAnd(...$categoriesConstraints);
                } else {
                    $constraints[] = $query->logicalOr(...$categoriesConstraints);
                }
            }
            if ($repositoryDemand->getTags() !== []) {
                $tagsConstraints = [];
                foreach ($repositoryDemand->getTags() as $tag) {
                    $tagsConstraints[] = $query->equals('tags.uid', $tag->getUid());
                }
                if ($repositoryDemand->getTagsConjunction() === Constants::REPOSITORY_CONJUNCTION_AND) {
                    $constraints[] = $query->logicalAnd(...$tagsConstraints);
                } else {
                    $constraints[] = $query->logicalOr(...$tagsConstraints);
                }
            }
            if (($ordering = $repositoryDemand->getOrdering()) !== []) {
                $query->setOrderings([$ordering['field'] => $ordering['direction']]);
            }
        }

        $query->matching($query->logicalAnd(...$constraints));

        if (($limit = $repositoryDemand->getLimit()) > 0) {
            $query->setLimit($limit);
        }

        /** @var Post[] $result */
        $result = $query->execute()->toArray();

        if ($repositoryDemand->getPosts() !== []) {
            // Sort manually selected posts by defined order in group field
            $sortedPosts = array_flip($repositoryDemand->getPosts());
            foreach ($result as $post) {
                $sortedPosts[$post->getUid()] = $post;
            }
            $result = array_values(array_filter($sortedPosts, function ($value) {
                return $value instanceof Post;
            }));
        }

        return $result;
    }

    /**
     * @return QueryResultInterface<int, Post>
     */
    public function findAll(): QueryResultInterface
    {
        $result = $this->getFindAllQuery()->execute();

        return $result;
    }

    public function findAllByPid(?int $blogSetup = null): QueryResultInterface
    {
        $query = $this->getFindAllQuery();

        if ($blogSetup !== null) {
            $constraints = [];
            if ($query->getConstraint() !== null) {
                $constraints[] = $query->getConstraint();
            }
            $constraints[] = $query->equals('pid', $blogSetup);
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query->execute();
    }

    /**
     * @return QueryResultInterface<int, Post>|array<int, Post>
     */
    public function findAllByPids(array $blogSetups): QueryResultInterface|array
    {
        $blogSetups = array_values(array_unique(array_filter(array_map('intval', $blogSetups), static fn (int $pid): bool => $pid > 0)));
        if ($blogSetups === []) {
            return [];
        }

        $query = $this->getFindAllQuery();
        $constraints = [];
        if ($query->getConstraint() !== null) {
            $constraints[] = $query->getConstraint();
        }
        $constraints[] = $query->in('pid', $blogSetups);
        $query->matching($query->logicalAnd(...$constraints));

        return $query->execute();
    }

    public function findAllWithLimit(int $limit): QueryResultInterface
    {
        $query = $this->getFindAllQuery();
        $query->setLimit($limit);

        return $query->execute();
    }

    /**
     * @return QueryInterface<Post>
     */
    protected function getFindAllQuery(): QueryInterface
    {
        /** @var QueryInterface<Post> $query */
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;
        $storagePidConstraint = $this->getStoragePidConstraint();
        if ($storagePidConstraint instanceof ComparisonInterface) {
            $constraints[] = $storagePidConstraint;
        }
        $constraints[] = $query->logicalOr(
            $query->equals('archiveDate', 0),
            $query->greaterThanOrEqual('archiveDate', time()),
        );

        $query->matching($query->logicalAnd(...$constraints));

        return $query;
    }

    public function findAllByAuthor(Author $author): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;
        $storagePidConstraint = $this->getStoragePidConstraint();
        if ($storagePidConstraint instanceof ComparisonInterface) {
            $constraints[] = $storagePidConstraint;
        }
        $constraints[] = $query->contains('authors', $author);

        return $query->matching($query->logicalAnd(...$constraints))->execute();
    }

    public function findAllByCategory(Category $category): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;
        $constraints[] = $query->contains('categories', $category);
        $storagePidConstraint = $this->getStoragePidConstraint();
        if ($storagePidConstraint instanceof ComparisonInterface) {
            $constraints[] = $storagePidConstraint;
        }

        return $query->matching($query->logicalAnd(...$constraints))->execute();
    }

    public function findAllByTag(Tag $tag): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;
        $constraints[] = $query->contains('tags', $tag);
        $storagePidConstraint = $this->getStoragePidConstraint();
        if ($storagePidConstraint instanceof ComparisonInterface) {
            $constraints[] = $storagePidConstraint;
        }

        return $query->matching($query->logicalAnd(...$constraints))->execute();
    }

    public function findByMonthAndYear(int $year, ?int $month = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;
        $storagePidConstraint = $this->getStoragePidConstraint();
        if ($storagePidConstraint instanceof ComparisonInterface) {
            $constraints[] = $storagePidConstraint;
        }

        if ($month !== null) {
            $startDate = new \DateTimeImmutable(sprintf('%d-%d-1 00:00:00', $year, $month));
            $endDate = new \DateTimeImmutable(sprintf('%d-%d-%d 23:59:59', $year, $month, (int)$startDate->format('t')));
        } else {
            $startDate = new \DateTimeImmutable(sprintf('%d-1-1 00:00:00', $year));
            $endDate = new \DateTimeImmutable(sprintf('%d-12-31 23:59:59', $year));
        }
        $constraints[] = $query->greaterThanOrEqual('publish_date', $startDate->getTimestamp());
        $constraints[] = $query->lessThanOrEqual('publish_date', $endDate->getTimestamp());

        return $query->matching($query->logicalAnd(...$constraints))->execute();
    }

    public function findCurrentPost(): ?Post
    {
        $pageInformation = RequestUtility::getPageInformation($this->getRequest());
        if ($pageInformation === null) {
            return null;
        }

        $pageId = $pageInformation->getId();
        $currentLanguageId = TypeUtility::toInt(
            GeneralUtility::makeInstance(Context::class)
                ->getPropertyFromAspect('language', 'id', 0),
        );

        $post = $this->getPostWithLanguage($pageId, $currentLanguageId);
        if ($post !== null) {
            return $post;
        }

        return $this->applyLanguageFallback($pageId, $currentLanguageId);
    }

    protected function getPostWithLanguage(int $pageId, int $languageId): ?Post
    {
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;

        if ($languageId > 0) {
            $constraints[] = $query->equals('l10n_parent', $pageId);
            $constraints[] = $query->equals('sys_language_uid', $languageId);
        } else {
            $constraints[] = $query->equals('uid', $pageId);
        }

        /** @var null|Post */
        $result = $query
            ->matching($query->logicalAnd(...$constraints))
            ->execute()
            ->getFirst();

        return $result;
    }

    protected function applyLanguageFallback(int $pageId, int $currentLanguageId): ?Post
    {
        $currentSite = $this->getCurrentSite();
        if ($currentSite !== null) {
            $languageConfiguration = $currentSite->getAllLanguages()[$currentLanguageId] ?? null;
            if (!$languageConfiguration instanceof SiteLanguage) {
                return null;
            }
            // check the whole language-fallback chain
            $fallbacks = $languageConfiguration->getFallbackLanguageIds();
            foreach ($fallbacks as $fallbackLanguageId) {
                $post = $this->getPostWithLanguage($pageId, $fallbackLanguageId);
                if ($post !== null) {
                    return $post;
                }
            }
        }
        return null;
    }

    protected function getCurrentSite(): ?Site
    {
        return RequestUtility::getSite($this->getRequest());
    }

    public function findMonthsAndYearsWithPosts(): array
    {
        $query = $this->createQuery();
        $constraints = $this->defaultConstraints;
        $storagePidConstraint = $this->getStoragePidConstraint();
        if ($storagePidConstraint instanceof ComparisonInterface) {
            $constraints[] = $storagePidConstraint;
        }
        $constraints[] = $query->greaterThan('crdateMonth', 0);
        $constraints[] = $query->greaterThan('crdateYear', 0);
        $query->matching($query->logicalAnd(...$constraints));
        $posts = $query->execute(true);

        $result = [];
        $currentIndex = -1;
        $currentYear = null;
        $currentMonth = null;
        foreach ($posts as $post) {
            $year = $post['crdate_year'];
            $month = $post['crdate_month'];
            if ($currentYear !== $year || $currentMonth !== $month) {
                $currentIndex++;
                $currentYear = $year;
                $currentMonth = $month;
                $result[$currentIndex] = [
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'count' => 1,
                ];
            } else {
                $result[$currentIndex]['count']++;
            }
        }

        return $result;
    }

    protected function getStoragePidsFromTypoScript(): array
    {
        return GeneralUtility::intExplode(',', TypeUtility::toString($this->settings['persistence']['storagePid'] ?? ''));
    }

    /**
     */
    protected function getStoragePidConstraint(): ?ComparisonInterface
    {
        if (ApplicationType::fromRequest($this->getRequest())->isFrontend()) {
            $pids = $this->getPidsForConstraints();
            $query = $this->createQuery();
            return $query->in('pid', $pids);
        }
        return null;
    }

    protected function getPidsForConstraints(): array
    {
        // only add non empty pids (pid 0 will be removed as well
        $pids = array_filter($this->getStoragePidsFromTypoScript(), function ($value) {
            return $value !== '' && (int) $value !== 0;
        });

        if (count($pids) === 0) {
            $pageInformation = RequestUtility::getPageInformation($this->getRequest());
            if ($pageInformation === null) {
                return $pids;
            }

            foreach ($pageInformation->getLocalRootLine() as $value) {
                $pids[] = TypeUtility::toInt(is_array($value) ? ($value['uid'] ?? null) : null);
            }
        }

        return $pids;
    }

    private function getRequest(): ServerRequestInterface
    {
        return RequestUtility::getGlobalRequest();
    }
}
