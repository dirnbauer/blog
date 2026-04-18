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
use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Domain\Model\Category;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Model\Tag;
use T3G\AgencyPack\Blog\Domain\Repository\AuthorRepository;
use T3G\AgencyPack\Blog\Domain\Repository\CategoryRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use T3G\AgencyPack\Blog\Domain\Repository\TagRepository;
use T3G\AgencyPack\Blog\Factory\PostRepositoryDemandFactory;
use T3G\AgencyPack\Blog\Pagination\BlogPagination;
use T3G\AgencyPack\Blog\Service\CacheService;
use T3G\AgencyPack\Blog\Service\MetaTagService;
use T3G\AgencyPack\Blog\Service\RelatedPostsService;
use T3G\AgencyPack\Blog\Utility\ArchiveUtility;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\View\ViewInterface;

class PostController extends ActionController
{
    public function __construct(
        protected readonly PostRepository $postRepository,
        protected readonly AuthorRepository $authorRepository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly TagRepository $tagRepository,
        protected readonly CacheService $blogCacheService,
        protected readonly PostRepositoryDemandFactory $postRepositoryDemandFactory,
        protected readonly MetaTagService $metaTagService,
        protected readonly RelatedPostsService $relatedPostsService,
    ) {
    }

    /**
     * @param ViewInterface $view
     */
    protected function initializeView($view): void
    {
        if ($this->request->getFormat() === 'rss') {
            $action = '.' . $this->request->getControllerActionName();
            $arguments = [];
            switch ($action) {
                case '.listPostsByCategory':
                    if (isset($this->arguments['category'])) {
                        $category = $this->arguments['category']->getValue();
                        if ($category instanceof Category) {
                            $arguments[] = $category->getTitle();
                        }
                    }
                    break;
                case '.listPostsByDate':
                    $year = $this->arguments['year']->getValue();
                    if (is_numeric($year)) {
                        $arguments[] = (int)$year;
                    }
                    if (isset($this->arguments['month'])) {
                        $month = $this->arguments['month']->getValue();
                        if (is_numeric($month)) {
                            $arguments[] = (int)$month;
                        }
                    }
                    break;
                case '.listPostsByTag':
                    if (isset($this->arguments['tag'])) {
                        $tag = $this->arguments['tag']->getValue();
                        if ($tag instanceof Tag) {
                            $arguments[] = $tag->getTitle();
                        }
                    }
                    break;
                case '.listPostsByAuthor':
                    if (isset($this->arguments['author'])) {
                        $author = $this->arguments['author']->getValue();
                        if ($author instanceof Author) {
                            $arguments[] = $author->getName();
                        }
                    }
                    break;
                default:
            }

            $feedData = [
                'title' => LocalizationUtility::translate('feed.title' . $action, 'blog', $arguments),
                'description' => LocalizationUtility::translate('feed.description' . $action, 'blog', $arguments),
                'language' => $this->getSiteLanguage()->getLocale()->getLanguageCode(),
                'link' => $this->getRequestUrl(),
                'date' => date('r'),
            ];
            $this->view->assign('feed', $feedData);
        }

        $contentObject = RequestUtility::getCurrentContentObject($this->getRequest());
        $this->view->assign('data', $contentObject !== null ? $contentObject->data : null);
    }

    /**
     * Show a list of recent posts.
     */
    public function listRecentPostsAction(int $currentPage = 1): ResponseInterface
    {
        $maximumItems = (int) ($this->settings['lists']['posts']['maximumDisplayedItems'] ?? 0);
        $posts = (0 === $maximumItems)
            ? $this->postRepository->findAll()
            : $this->postRepository->findAllWithLimit($maximumItems);
        $pagination = $this->getPagination($posts, $currentPage);

        $this->view->assign('type', 'recent');
        $this->view->assign('posts', $posts);
        $this->view->assign('pagination', $pagination);
        return $this->htmlResponse();
    }

    /**
     * Show a list of posts for a selected category.
     */
    public function listByDemandAction(): ResponseInterface
    {
        $repositoryDemand = $this->postRepositoryDemandFactory->createFromSettings($this->settings['demand'] ?? []);

        $this->view->assign('type', 'demand');
        $this->view->assign('demand', $repositoryDemand);
        $this->view->assign('posts', $this->postRepository->findByRepositoryDemand($repositoryDemand));
        $this->view->assign('pagination', []);
        return $this->htmlResponse();
    }

    /**
     * Show a number of latest posts.
     */
    public function listLatestPostsAction(): ResponseInterface
    {
        $maximumItems = (int) ($this->settings['latestPosts']['limit'] ?? 3);
        $posts = $this->postRepository->findAllWithLimit($maximumItems);

        $this->view->assign('type', 'latest');
        $this->view->assign('posts', $posts);
        return $this->htmlResponse();
    }

    public function listPostsByDateAction(?int $year = null, ?int $month = null, int $currentPage = 1): ResponseInterface
    {
        if ($year === null) {
            $posts = $this->postRepository->findMonthsAndYearsWithPosts();
            $this->view->assign('archiveData', ArchiveUtility::extractDataFromPosts($posts));
        } else {
            $dateTime = new \DateTimeImmutable(sprintf('%d-%d-1', $year, $month ?? 1));
            $posts = $this->postRepository->findByMonthAndYear($year, $month);
            $pagination = $this->getPagination($posts, $currentPage);
            $this->view->assign('type', 'bydate');
            $this->view->assign('month', $month);
            $this->view->assign('year', $year);
            $this->view->assign('timestamp', $dateTime->getTimestamp());
            $this->view->assign('posts', $posts);
            $this->view->assign('pagination', $pagination);
            $title = str_replace([
                '###MONTH###',
                '###MONTH_NAME###',
                '###YEAR###',
            ], [
                (string) $month,
                $dateTime->format('F'),
                (string) $year,
            ], (string) LocalizationUtility::translate('meta.title.listPostsByDate', 'blog'));
            $this->metaTagService->set(MetaTagService::META_TITLE, (string) $title);
            $this->metaTagService->set(MetaTagService::META_DESCRIPTION, (string) LocalizationUtility::translate('meta.description.listPostsByDate', 'blog'));
        }
        return $this->htmlResponse();
    }

    /**
     * Show a list of posts by given category.
     */
    public function listPostsByCategoryAction(?Category $category = null, int $currentPage = 1): ResponseInterface
    {
        if ($category === null) {
            $contentObject = RequestUtility::getCurrentContentObject($this->getRequest());
            $referenceUid = $this->getContentObjectUid($contentObject);
            if ($referenceUid !== null) {
                $categories = $this->categoryRepository->getByReference('tt_content', $referenceUid);
                if ($categories !== null && $categories->count() > 0) {
                    /** @var ?Category $category */
                    $category = $categories->getFirst();
                }
            }
        }

        if ($category !== null) {
            $posts = $this->postRepository->findAllByCategory($category);
            $pagination = $this->getPagination($posts, $currentPage);
            $this->view->assign('type', 'bycategory');
            $this->view->assign('posts', $posts);
            $this->view->assign('pagination', $pagination);
            $this->view->assign('category', $category);
            $this->metaTagService->set(MetaTagService::META_TITLE, (string) $category->getTitle());
            $this->metaTagService->set(MetaTagService::META_DESCRIPTION, (string) $category->getDescription());
        } else {
            $this->view->assign('categories', $this->categoryRepository->findAll());
        }
        return $this->htmlResponse();
    }

    /**
     * Show a list of posts by given author.
     */
    public function listPostsByAuthorAction(?Author $author = null, int $currentPage = 1): ResponseInterface
    {
        if ($author !== null) {
            $posts = $this->postRepository->findAllByAuthor($author);
            $pagination = $this->getPagination($posts, $currentPage);
            $this->view->assign('type', 'byauthor');
            $this->view->assign('posts', $posts);
            $this->view->assign('pagination', $pagination);
            $this->view->assign('author', $author);
            $this->metaTagService->set(MetaTagService::META_TITLE, (string) $author->getName());
            $this->metaTagService->set(MetaTagService::META_DESCRIPTION, (string) $author->getBio());
        } else {
            $this->view->assign('authors', $this->authorRepository->findAll());
        }
        return $this->htmlResponse();
    }

    /**
     * Show a list of posts by given tag.
     */
    public function listPostsByTagAction(?Tag $tag = null, int $currentPage = 1): ResponseInterface
    {
        if ($tag !== null) {
            $posts = $this->postRepository->findAllByTag($tag);
            $pagination = $this->getPagination($posts, $currentPage);
            $this->view->assign('type', 'bytag');
            $this->view->assign('posts', $posts);
            $this->view->assign('pagination', $pagination);
            $this->view->assign('tag', $tag);
            $this->metaTagService->set(MetaTagService::META_TITLE, (string) $tag->getTitle());
            $this->metaTagService->set(MetaTagService::META_DESCRIPTION, (string) $tag->getDescription());
        } else {
            $this->view->assign('tags', $this->tagRepository->findAll());
        }
        return $this->htmlResponse();
    }

    /**
     * Sidebar action.
     */
    public function sidebarAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * Header action: output the header of blog post.
     */
    public function headerAction(): ResponseInterface
    {
        $post = $this->postRepository->findCurrentPost();
        $this->view->assign('post', $post);
        if ($post instanceof Post) {
            $this->blogCacheService->addTagsForPost($this->request, $post);
        }
        return $this->htmlResponse();
    }

    /**
     * Footer action: output the footer of blog post.
     */
    public function footerAction(): ResponseInterface
    {
        $post = $this->postRepository->findCurrentPost();
        $this->view->assign('post', $post);
        if ($post instanceof Post) {
            $this->blogCacheService->addTagsForPost($this->request, $post);
        }
        return $this->htmlResponse();
    }

    /**
     * Authors action: output author information of blog post.
     */
    public function authorsAction(): ResponseInterface
    {
        $post = $this->postRepository->findCurrentPost();
        $this->view->assign('post', $post);
        if ($post instanceof Post) {
            $this->blogCacheService->addTagsForPost($this->request, $post);
        }
        return $this->htmlResponse();
    }

    /**
     * Related posts action: show related posts based on the current post
     */
    public function relatedPostsAction(): ResponseInterface
    {
        $post = $this->postRepository->findCurrentPost();
        $posts = $this->relatedPostsService->findRelatedPosts(
            (int)$this->settings['relatedPosts']['categoryMultiplier'],
            (int)$this->settings['relatedPosts']['tagMultiplier'],
            (int)$this->settings['relatedPosts']['limit'],
        );
        $this->view->assign('type', 'related');
        $this->view->assign('post', $post);
        $this->view->assign('posts', $posts);
        return $this->htmlResponse();
    }

    private function getRequest(): ServerRequestInterface
    {
        return RequestUtility::getGlobalRequest();
    }

    private function getSiteLanguage(): SiteLanguage
    {
        return RequestUtility::getSiteLanguage($this->getRequest());
    }

    private function getRequestUrl(): string
    {
        /** @var NormalizedParams $normalizedParams */
        $normalizedParams = RequestUtility::getNormalizedParams($this->getRequest());
        return $normalizedParams->getRequestUrl();
    }

    private function getContentObjectUid(?ContentObjectRenderer $contentObject): ?int
    {
        if ($contentObject === null) {
            return null;
        }

        $uid = $contentObject->data['uid'] ?? null;

        return is_numeric($uid) ? (int)$uid : null;
    }

    protected function getPagination(QueryResultInterface $objects, int $currentPage = 1): ?BlogPagination
    {
        $maximumNumberOfLinks = (int) ($this->settings['lists']['pagination']['maximumNumberOfLinks'] ?? 0);
        $itemsPerPage = 10;
        if ($this->request->getFormat() === 'html') {
            $itemsPerPage = (int) ($this->settings['lists']['pagination']['itemsPerPage'] ?? 10);
        }

        $paginator = new QueryResultPaginator($objects, $currentPage, $itemsPerPage);
        return new BlogPagination($paginator, $maximumNumberOfLinks);
    }
}
