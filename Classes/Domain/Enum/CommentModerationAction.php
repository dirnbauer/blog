<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Enum;

use T3G\AgencyPack\Blog\Domain\Model\Comment;

/**
 * Backend moderation actions for comments.
 */
enum CommentModerationAction: string
{
    case Approve = 'approve';
    case Decline = 'decline';
    case Delete = 'delete';

    public function targetStatus(): int
    {
        return match ($this) {
            self::Approve => Comment::STATUS_APPROVED,
            self::Decline => Comment::STATUS_DECLINED,
            self::Delete => Comment::STATUS_DELETED,
        };
    }

    public static function tryFromAction(string $action): ?self
    {
        return self::tryFrom($action);
    }
}
