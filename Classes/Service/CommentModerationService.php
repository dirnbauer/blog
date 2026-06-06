<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\Domain\Enum\CommentModerationAction;
use T3G\AgencyPack\Blog\Domain\Model\Comment;

final class CommentModerationService
{
    public function isValidAction(string $action): bool
    {
        return CommentModerationAction::tryFromAction($action) instanceof CommentModerationAction;
    }

    /**
     * @param array<array-key, mixed> $comments
     *
     * @return list<int>
     */
    public function resolveCommentIds(array $comments, ?int $singleCommentId = null): array
    {
        $ids = [];
        $identities = $comments['__identity'] ?? [];
        if (is_iterable($identities)) {
            foreach ($identities as $commentId) {
                if (is_scalar($commentId)) {
                    $ids[] = (int)$commentId;
                }
            }
        }
        if ($singleCommentId !== null) {
            $ids[] = $singleCommentId;
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    public function applyAction(Comment $comment, string $action): bool
    {
        $moderationAction = CommentModerationAction::tryFromAction($action);
        if (!$moderationAction instanceof CommentModerationAction) {
            return false;
        }

        $comment->setStatus($moderationAction->targetStatus());

        return true;
    }
}
