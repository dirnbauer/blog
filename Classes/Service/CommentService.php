<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class CommentService
{
    public const STATE_ERROR = 'error';
    public const STATE_MODERATION = 'moderation';
    public const STATE_SUCCESS = 'success';

    public function __construct(
        protected readonly PostRepository $postRepository,
        protected readonly CommentRepository $commentRepository,
        protected readonly PersistenceManagerInterface $persistenceManager,
    ) {
    }

    protected array $settings = [
        'active' => 0,
        'moderation' => 0,
    ];

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function addComment(Post $post, Comment $comment): string
    {
        $result = self::STATE_ERROR;
        if (trim((string) $comment->getHp()) !== '') {
            return $result;
        }
        if ((int)$this->settings['active'] === 1 && $post->getCommentsActive()) {
            $result = self::STATE_SUCCESS;
            switch ((int)$this->settings['moderation']) {
                case 0:
                    $comment->setStatus(Comment::STATUS_APPROVED);
                    break;
                case 1:
                case 2:
                    $result = self::STATE_MODERATION;
                    $comment->setStatus(Comment::STATUS_PENDING);
                    break;
                default:
            }
            $comment->setPid((int)$post->getUid());
            $comment->setPostLanguageId(GeneralUtility::makeInstance(Context::class)->getAspect('language')->getId());
            $post->addComment($comment);
            $this->postRepository->update($post);
            $this->persistenceManager->persistAll();
        }

        return $result;
    }

    public function getCommentsByPost(Post $post): QueryResultInterface
    {
        return $this->commentRepository->findAllByPost($post);
    }
}
