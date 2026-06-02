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

final class CommentModerationService
{
    private const VALID_ACTIONS = ['approve', 'decline', 'delete'];

    public function isValidAction(string $action): bool
    {
        return in_array($action, self::VALID_ACTIONS, true);
    }

    /**
     * @param array<string, mixed> $comments
     *
     * @return list<int>
     */
    public function resolveCommentIds(array $comments, ?int $singleCommentId = null): array
    {
        $ids = [];
        foreach ($comments['__identity'] ?? [] as $commentId) {
            $ids[] = (int)$commentId;
        }
        if ($singleCommentId !== null) {
            $ids[] = $singleCommentId;
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    public function applyAction(Comment $comment, string $action): bool
    {
        return match ($action) {
            'approve' => $this->setStatus($comment, Comment::STATUS_APPROVED),
            'decline' => $this->setStatus($comment, Comment::STATUS_DECLINED),
            'delete' => $this->setStatus($comment, Comment::STATUS_DELETED),
            default => false,
        };
    }

    private function setStatus(Comment $comment, int $status): bool
    {
        $comment->setStatus($status);

        return true;
    }
}
