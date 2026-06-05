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
use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupCreateRequest;
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Service\BackendAccessService;
use T3G\AgencyPack\Blog\Service\BackendBlogContextService;
use T3G\AgencyPack\Blog\Service\BackendBlogListingService;
use T3G\AgencyPack\Blog\Service\CacheService;
use T3G\AgencyPack\Blog\Service\CommentModerationService;
use T3G\AgencyPack\Blog\Service\SetupService;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BackendController extends ActionController
{
    public function __construct(
        protected readonly CommentRepository $commentRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly SetupService $setupService,
        protected readonly CacheService $cacheService,
        protected readonly BackendAccessService $backendAccessService,
        protected readonly CommentModerationService $commentModerationService,
        protected readonly BackendBlogContextService $backendBlogContextService,
        protected readonly BackendBlogListingService $backendBlogListingService,
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
        $blogSetups = $this->setupService->determineBlogSetups();
        $activeBlogSetup = $this->backendBlogContextService->resolveActiveBlogSetup($blogSetup, $blogSetups);

        $view = $this->moduleTemplateFactory->create($this->request);
        $view->assignMultiple([
            'blogSetups' => $blogSetups,
            'activeBlogSetup' => $activeBlogSetup,
            'posts' => $this->backendBlogListingService->findPostsForSelection($activeBlogSetup, $blogSetups),
        ]);

        return $view->renderResponse('Backend/Posts');
    }

    public function commentsAction(?string $filter = null, ?int $blogSetup = null): ResponseInterface
    {
        $blogSetups = $this->setupService->determineBlogSetups();
        $activeBlogSetup = $this->backendBlogContextService->resolveActiveBlogSetup($blogSetup, $blogSetups);
        $accessibleIds = $this->backendBlogContextService->getAccessibleIds($blogSetups);

        $view = $this->moduleTemplateFactory->create($this->request);
        $view->assignMultiple([
            'activeFilter' => $filter,
            'activeBlogSetup' => $activeBlogSetup,
            'commentCounts' => $this->backendBlogListingService->countCommentsForSelection($activeBlogSetup, $accessibleIds),
            'blogSetups' => $blogSetups,
            'comments' => $this->backendBlogListingService->findCommentsForSelection($filter, $activeBlogSetup, $accessibleIds),
        ]);

        return $view->renderResponse('Backend/Comments');
    }

    public function updateCommentStatusAction(string $status, ?string $filter = null, ?int $blogSetup = null, array $comments = [], ?int $comment = null): ResponseInterface
    {
        $permissionDenied = false;
        $updatedComment = false;

        foreach ($this->commentModerationService->resolveCommentIds($comments, $comment) as $commentId) {
            $commentEntity = $this->commentRepository->findByUid($commentId);
            if (!$commentEntity instanceof Comment) {
                continue;
            }
            if (!$this->backendAccessService->canModerateComment($commentEntity)) {
                $permissionDenied = true;
                continue;
            }
            if ($this->commentModerationService->applyAction($commentEntity, $status)) {
                $updatedComment = true;
                $this->commentRepository->update($commentEntity);
                $this->cacheService->flushCacheByTag('tx_blog_comment_' . $commentEntity->getUid());
            }
        }
        if ($permissionDenied) {
            $this->addFlashMessage(
                $this->translate('flash.comment.permission_denied.message'),
                $this->translate('flash.permission_denied.title'),
                ContextualFeedbackSeverity::ERROR,
            );
        }
        if (!$updatedComment && !$this->commentModerationService->isValidAction($status)) {
            $this->addFlashMessage(
                $this->translate('flash.comment.invalid_status.message'),
                $this->translate('flash.invalid_action.title'),
                ContextualFeedbackSeverity::ERROR,
            );
        }

        return new RedirectResponse($this->uriBuilder->reset()->uriFor('comments', ['filter' => $filter, 'blogSetup' => $blogSetup]));
    }

    public function createBlogAction(?array $data = null): ResponseInterface
    {
        if ($this->backendAccessService->getBackendUser()?->isAdmin() !== true) {
            $this->addFlashMessage(
                $this->translate('flash.setup.permission_denied.message'),
                $this->translate('flash.permission_denied.title'),
                ContextualFeedbackSeverity::ERROR,
            );

            return new RedirectResponse($this->uriBuilder->reset()->uriFor('setupWizard'));
        }

        $createRequest = BlogSetupCreateRequest::fromRequestData($data);
        if ($createRequest instanceof BlogSetupCreateRequest) {
            $this->setupService->createBlogSetup($createRequest);
            $this->addFlashMessage(
                $this->translate('flash.setup.created.message'),
                $this->translate('flash.setup.created.title'),
            );
        } else {
            $this->addFlashMessage(
                $this->translate('flash.setup.create_failed.message'),
                $this->translate('flash.error.title'),
                ContextualFeedbackSeverity::ERROR,
            );
        }

        return new RedirectResponse($this->uriBuilder->reset()->uriFor('setupWizard'));
    }

    protected function translate(string $key): string
    {
        return LocalizationUtility::translate($key, 'Blog') ?? $key;
    }
}
