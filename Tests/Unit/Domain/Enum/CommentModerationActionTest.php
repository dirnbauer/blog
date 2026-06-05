<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Domain\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\Blog\Domain\Enum\CommentModerationAction;
use T3G\AgencyPack\Blog\Domain\Model\Comment;

final class CommentModerationActionTest extends TestCase
{
    #[Test]
    public function targetStatusMapsApproveToApproved(): void
    {
        self::assertSame(Comment::STATUS_APPROVED, CommentModerationAction::Approve->targetStatus());
    }

    #[Test]
    public function tryFromActionRejectsUnknownAction(): void
    {
        self::assertNull(CommentModerationAction::tryFromAction('spam'));
    }
}
