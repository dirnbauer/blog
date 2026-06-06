<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Loads the current blog post for frontend plugins and assigns it to the view.
 */
final class PostPageContextService
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CacheService $cacheService,
    ) {
    }

    public function assignCurrentPostToView(ViewInterface $view, ServerRequestInterface $request): ?Post
    {
        $post = $this->postRepository->findCurrentPost();
        $view->assign('post', $post);
        if ($post instanceof Post) {
            $this->cacheService->addTagsForPost($request, $post);
        }

        return $post;
    }
}
