<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Service\CommentModerationService;

final class CommentModerationServiceTest extends TestCase
{
    private CommentModerationService $subject;

    protected function setUp(): void
    {
        $this->subject = new CommentModerationService();
    }

    #[Test]
    public function resolveCommentIdsMergesFormAndSingleId(): void
    {
        self::assertSame(
            [1, 2, 3],
            $this->subject->resolveCommentIds(['__identity' => [1, 2]], 3),
        );
    }

    #[Test]
    public function resolveCommentIdsDeduplicatesAndDropsZero(): void
    {
        self::assertSame(
            [5],
            $this->subject->resolveCommentIds(['__identity' => [5, 5, 0]], null),
        );
    }

    #[Test]
    public function applyActionSetsApprovedStatus(): void
    {
        $comment = new Comment();
        self::assertTrue($this->subject->applyAction($comment, 'approve'));
        self::assertSame(Comment::STATUS_APPROVED, $comment->getStatus());
    }

    #[Test]
    public function applyActionRejectsUnknownAction(): void
    {
        $comment = new Comment();
        self::assertFalse($this->subject->applyAction($comment, 'spam'));
    }

    #[Test]
    public function isValidActionRecognizesModerationActions(): void
    {
        self::assertTrue($this->subject->isValidAction('decline'));
        self::assertFalse($this->subject->isValidAction('invalid'));
    }
}
