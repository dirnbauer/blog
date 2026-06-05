<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupSummary;
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;

/**
 * Backend module queries for posts and comments scoped to accessible blog setups.
 */
final class BackendBlogListingService
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly BackendBlogContextService $backendBlogContextService,
        private readonly BackendAccessService $backendAccessService,
    ) {
    }

    /**
     * @param list<BlogSetupSummary> $blogSetups
     */
    public function findPostsForSelection(?int $blogSetup, array $blogSetups): iterable
    {
        if (!$this->backendAccessService->canReadTable('pages')) {
            return [];
        }
        if ($blogSetup !== null) {
            return $this->postRepository->findAllByPidForBackend($blogSetup);
        }

        return $this->postRepository->findAllByPidsForBackend(
            $this->backendBlogContextService->getAccessibleIds($blogSetups),
        );
    }

    public function findCommentsForSelection(?string $filter, ?int $blogSetup, array $accessibleBlogSetupIds): iterable
    {
        if (!$this->backendAccessService->canReadTable('tx_blog_domain_model_comment')) {
            return [];
        }
        if ($blogSetup !== null) {
            return $this->commentRepository->findAllByFilter($filter, $blogSetup);
        }

        return $this->commentRepository->findAllByFilterAndBlogSetups($filter, $accessibleBlogSetupIds);
    }

    /**
     * @return array{all: int, pending: int, approved: int, declined: int, deleted: int}
     */
    public function countCommentsForSelection(?int $blogSetup, array $accessibleBlogSetupIds): array
    {
        if (!$this->backendAccessService->canReadTable('tx_blog_domain_model_comment')) {
            return [
                'all' => 0,
                'pending' => 0,
                'approved' => 0,
                'declined' => 0,
                'deleted' => 0,
            ];
        }

        return $this->commentRepository->countByFilterForBlogSetups($blogSetup, $accessibleBlogSetupIds);
    }
}
