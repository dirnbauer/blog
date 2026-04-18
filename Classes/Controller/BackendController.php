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
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use T3G\AgencyPack\Blog\Service\BackendAccessService;
use T3G\AgencyPack\Blog\Service\CacheService;
use T3G\AgencyPack\Blog\Service\SetupService;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BackendController extends ActionController
{
    public function __construct(
        protected readonly PostRepository $postRepository,
        protected readonly CommentRepository $commentRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly SetupService $setupService,
        protected readonly CacheService $cacheService,
        protected readonly BackendAccessService $backendAccessService,
    ) {
    }

    public function initializeAction(): void
    {
        $this->pageRenderer->addCssFile('EXT:blog/Resources/Public/Css/backend.min.css', 'stylesheet', 'all', '', false);
    }

    public function initializeSetupWizardAction(): void
    {
        $this->initializeDataTables();
        $this->pageRenderer->loadJavaScriptModule('@t3g/blog/setup-wizard.js');
    }

    public function initializePostsAction(): void
    {
        $this->initializeDataTables();
    }

    public function initializeCommentsAction(): void
    {
        $this->initializeDataTables();
        $this->pageRenderer->loadJavaScriptModule('@t3g/blog/mass-update.js');
    }

    protected function initializeDataTables(): void
    {
        $this->pageRenderer->loadJavaScriptModule('@t3g/blog/datatables.js');
        $this->pageRenderer->addCssFile('EXT:blog/Resources/Public/Css/datatables.min.css', 'stylesheet', 'all', '', false);
    }

    public function setupWizardAction(): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($this->request);
        $view->assignMultiple([
            'blogSetups' => $this->setupService->determineBlogSetups(),
        ]);

        return $view->renderResponse('Backend/SetupWizard');
    }

    public function postsAction(?int $blogSetup = null): ResponseInterface
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $this->postRepository->setDefaultQuerySettings($querySettings);
        $blogSetups = $this->setupService->determineBlogSetups();
        $blogSetupIds = $this->extractBlogSetupIds($blogSetups);
        $activeBlogSetup = $this->resolveActiveBlogSetup($blogSetup, $blogSetupIds);

        $view = $this->moduleTemplateFactory->create($this->request);
        $view->assignMultiple([
            'blogSetups' => $blogSetups,
            'activeBlogSetup' => $activeBlogSetup,
            'posts' => $this->getPostsForBlogSelection($activeBlogSetup, $blogSetupIds),
        ]);

        return $view->renderResponse('Backend/Posts');
    }

    public function commentsAction(?string $filter = null, ?int $blogSetup = null): ResponseInterface
    {
        $blogSetups = $this->setupService->determineBlogSetups();
        $blogSetupIds = $this->extractBlogSetupIds($blogSetups);
        $activeBlogSetup = $this->resolveActiveBlogSetup($blogSetup, $blogSetupIds);

        $view = $this->moduleTemplateFactory->create($this->request);
        $view->assignMultiple([
            'activeFilter' => $filter,
            'activeBlogSetup' => $activeBlogSetup,
            'commentCounts' => $this->getCommentCountsForBlogSelection($activeBlogSetup, $blogSetupIds),
            'blogSetups' => $blogSetups,
            'comments' => $this->getCommentsForBlogSelection($filter, $activeBlogSetup, $blogSetupIds),
        ]);

        return $view->renderResponse('Backend/Comments');
    }

    public function updateCommentStatusAction(string $status, ?string $filter = null, ?int $blogSetup = null, array $comments = [], ?int $comment = null): ResponseInterface
    {
        $permissionDenied = false;
        $updatedComment = false;
        if ($comment !== null) {
            $comments['__identity'][] = $comment;
        }
        foreach ($comments['__identity'] ?? [] as $commentId) {
            /** @var Comment|null $comment */
            $comment = $this->commentRepository->findByUid((int)$commentId);
            if (!$comment instanceof Comment) {
                continue;
            }
            if (!$this->backendAccessService->canModerateComment($comment)) {
                $permissionDenied = true;
                continue;
            }
            if ($this->applyCommentStatus($comment, $status)) {
                $updatedComment = true;
                $this->commentRepository->update($comment);
                $this->cacheService->flushCacheByTag('tx_blog_comment_' . $comment->getUid());
            }
        }
        if ($permissionDenied) {
            $this->addFlashMessage(
                'One or more comments were skipped because you do not have permission to moderate them.',
                'Permission denied',
                ContextualFeedbackSeverity::ERROR,
            );
        }
        if (!$updatedComment && !in_array($status, ['approve', 'decline', 'delete'], true)) {
            $this->addFlashMessage(
                'The requested comment status change is not supported.',
                'Invalid action',
                ContextualFeedbackSeverity::ERROR,
            );
        }

        return new RedirectResponse($this->uriBuilder->reset()->uriFor('comments', ['filter' => $filter, 'blogSetup' => $blogSetup]));
    }

    public function createBlogAction(?array $data = null): ResponseInterface
    {
        if ($this->backendAccessService->getBackendUser()?->isAdmin() !== true) {
            $this->addFlashMessage(
                'Only administrators may create a blog setup.',
                'Permission denied',
                ContextualFeedbackSeverity::ERROR,
            );

            return new RedirectResponse($this->uriBuilder->reset()->uriFor('setupWizard'));
        }

        if ($data !== null) {
            $this->setupService->createBlogSetup($data);
            $this->addFlashMessage('Your blog setup has been created.', 'Congratulation');
        } else {
            $this->addFlashMessage('Sorry, your blog setup could not be created.', 'An error occurred', ContextualFeedbackSeverity::ERROR);
        }

        return new RedirectResponse($this->uriBuilder->reset()->uriFor('setupWizard'));
    }

    protected function extractBlogSetupIds(array $blogSetups): array
    {
        return array_values(array_filter(array_map(static function (array $blogSetup): int {
            return (int)($blogSetup['uid'] ?? 0);
        }, $blogSetups), static fn (int $uid): bool => $uid > 0));
    }

    protected function resolveActiveBlogSetup(?int $blogSetup, array $accessibleBlogSetupIds): ?int
    {
        if ($blogSetup !== null && in_array($blogSetup, $accessibleBlogSetupIds, true)) {
            return $blogSetup;
        }

        return null;
    }

    protected function getPostsForBlogSelection(?int $blogSetup, array $accessibleBlogSetupIds): iterable
    {
        if (!$this->backendAccessService->canReadTable('pages')) {
            return [];
        }
        if ($blogSetup !== null) {
            return $this->postRepository->findAllByPid($blogSetup);
        }

        return $this->postRepository->findAllByPids($accessibleBlogSetupIds);
    }

    protected function getCommentsForBlogSelection(?string $filter, ?int $blogSetup, array $accessibleBlogSetupIds): iterable
    {
        if (!$this->backendAccessService->canReadTable('tx_blog_domain_model_comment')) {
            return [];
        }
        if ($blogSetup !== null) {
            return $this->commentRepository->findAllByFilter($filter, $blogSetup);
        }

        return $this->commentRepository->findAllByFilterAndBlogSetups($filter, $accessibleBlogSetupIds);
    }

    protected function getCommentCountsForBlogSelection(?int $blogSetup, array $accessibleBlogSetupIds): array
    {
        $counts = [
            'all' => 0,
            'pending' => 0,
            'approved' => 0,
            'declined' => 0,
            'deleted' => 0,
        ];
        if (!$this->backendAccessService->canReadTable('tx_blog_domain_model_comment')) {
            return $counts;
        }

        foreach ($counts as $filter => $_) {
            $currentFilter = $filter === 'all' ? null : $filter;
            $comments = $blogSetup !== null
                ? $this->commentRepository->findAllByFilter($currentFilter, $blogSetup)
                : $this->commentRepository->findAllByFilterAndBlogSetups($currentFilter, $accessibleBlogSetupIds);
            $counts[$filter] = count($comments);
        }

        return $counts;
    }

    protected function applyCommentStatus(Comment $comment, string $status): bool
    {
        switch ($status) {
            case 'approve':
                $comment->setStatus(Comment::STATUS_APPROVED);
                return true;
            case 'decline':
                $comment->setStatus(Comment::STATUS_DECLINED);
                return true;
            case 'delete':
                $comment->setStatus(Comment::STATUS_DELETED);
                return true;
            default:
                return false;
        }
    }
}
