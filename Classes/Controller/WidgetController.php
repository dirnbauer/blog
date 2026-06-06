<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Controller;

use Psr\Http\Message\ResponseInterface;
use T3G\AgencyPack\Blog\Domain\Model\Category;
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Repository\CategoryRepository;
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use T3G\AgencyPack\Blog\Domain\Repository\TagRepository;
use T3G\AgencyPack\Blog\Service\CacheService;
use T3G\AgencyPack\Blog\Utility\ArchiveUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class WidgetController extends ActionController
{
    public function __construct(
        protected readonly CategoryRepository $categoryRepository,
        protected readonly TagRepository $tagRepository,
        protected readonly PostRepository $postRepository,
        protected readonly CommentRepository $commentRepository,
        protected readonly CacheService $cacheService,
    ) {
    }

    public function categoriesAction(): ResponseInterface
    {
        // @todo allow post?
        $requestParameters = TypeUtility::toArray($this->request->getQueryParams()['tx_blog_category'] ?? null);
        $currentCategory = 0;
        if (($requestParameters['category'] ?? null) !== null) {
            $currentCategory = TypeUtility::toInt($requestParameters['category']);
        }
        $categories = $this->categoryRepository->findAll();
        $this->view->assign('categories', $categories);
        $this->view->assign('currentCategory', $currentCategory);
        foreach ($categories as $category) {
            if ($category instanceof Category) {
                $this->cacheService->addTagToPage($this->request, 'tx_blog_category_' . $category->getUid());
            }
        }
        return $this->htmlResponse();
    }

    public function tagsAction(): ResponseInterface
    {
        // @todo allow post?
        $requestParameters = TypeUtility::toArray($this->request->getQueryParams()['tx_blog_tag'] ?? null);
        $currentTag = 0;
        if (($requestParameters['tag'] ?? null) !== null) {
            $currentTag = TypeUtility::toInt($requestParameters['tag']);
        }
        $limit = TypeUtility::toInt(TypeUtility::nested($this->settings, 'widgets', 'tags', 'limit'), 20);
        $minSize = TypeUtility::toInt(TypeUtility::nested($this->settings, 'widgets', 'tags', 'minSize'), 100);
        $maxSize = TypeUtility::toInt(TypeUtility::nested($this->settings, 'widgets', 'tags', 'maxSize'), 100);
        $tags = $this->tagRepository->findTopByUsage($limit);
        $minimumCount = null;
        $maximumCount = 0;
        foreach ($tags as $tag) {
            $count = TypeUtility::toInt($tag['cnt'] ?? null);
            if ($count > $maximumCount) {
                $maximumCount = $count;
            }
            if ($minimumCount === null || $count < $minimumCount) {
                $minimumCount = $count;
            }
        }
        $minimumCount ??= 0;
        $spread = $maximumCount - $minimumCount;

        if ($spread === 0) {
            $spread = 1;
        }

        foreach ($tags as &$tagReference) {
            $size = $minSize + (TypeUtility::toInt($tagReference['cnt'] ?? null) - $minimumCount) * ($maxSize - $minSize) / $spread;
            $tagReference['size'] = floor($size);
        }
        unset($tagReference);
        foreach ($tags as $tag) {
            $this->cacheService->addTagToPage($this->request, 'tx_blog_tag_' . TypeUtility::toInt($tag['uid'] ?? null));
        }
        $this->view->assign('tags', $tags);
        $this->view->assign('currentTag', $currentTag);
        return $this->htmlResponse();
    }

    public function recentPostsAction(): ResponseInterface
    {
        $limit = TypeUtility::toInt(TypeUtility::nested($this->settings, 'widgets', 'recentposts', 'limit'));

        $posts = $limit > 0
            ? $this->postRepository->findAllWithLimit($limit)
            : $this->postRepository->findAll();

        foreach ($posts as $post) {
            if ($post instanceof Post) {
                $this->cacheService->addTagsForPost($this->request, $post);
            }
        }
        $this->view->assign('posts', $posts);
        return $this->htmlResponse();
    }

    public function commentsAction(): ResponseInterface
    {
        $limit = TypeUtility::toInt(TypeUtility::nested($this->settings, 'widgets', 'comments', 'limit'), 5);
        $blogSetupValue = TypeUtility::nested($this->settings, 'widgets', 'comments', 'blogSetup');
        $blogSetup = $blogSetupValue !== null ? TypeUtility::toInt($blogSetupValue) : null;
        $comments = $this->commentRepository->findActiveComments($limit, $blogSetup);
        $this->view->assign('comments', $comments);
        foreach ($comments as $comment) {
            if ($comment instanceof Comment) {
                $this->cacheService->addTagToPage($this->request, 'tx_blog_comment_' . $comment->getUid());
            }
        }
        return $this->htmlResponse();
    }

    public function archiveAction(): ResponseInterface
    {
        $posts = $this->postRepository->findMonthsAndYearsWithPosts();
        $this->view->assign('archiveData', ArchiveUtility::extractDataFromPosts($posts));
        return $this->htmlResponse();
    }

    public function feedAction(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->getBody()->write($this->view->render());
        return $response;
    }
}
