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
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use T3G\AgencyPack\Blog\Service\BackendAccessService;
use T3G\AgencyPack\Blog\Service\BackendBlogContextService;
use T3G\AgencyPack\Blog\Service\BackendBlogListingService;

final class BackendBlogListingServiceTest extends TestCase
{
    private BackendBlogListingService $subject;

    protected function setUp(): void
    {
        $this->subject = new BackendBlogListingService(
            $this->createMock(PostRepository::class),
            $this->createMock(CommentRepository::class),
            new BackendBlogContextService(),
            new BackendAccessService(),
        );
    }

    #[Test]
    public function findPostsForSelectionReturnsEmptyArrayWithoutBackendUser(): void
    {
        self::assertSame([], iterator_to_array($this->subject->findPostsForSelection(null, [])));
    }

    #[Test]
    public function countCommentsForSelectionReturnsZerosWithoutBackendUser(): void
    {
        self::assertSame(
            ['all' => 0, 'pending' => 0, 'approved' => 0, 'declined' => 0, 'deleted' => 0],
            $this->subject->countCommentsForSelection(null, []),
        );
    }
}
