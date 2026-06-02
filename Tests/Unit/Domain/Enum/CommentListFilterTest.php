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
use T3G\AgencyPack\Blog\Domain\Enum\CommentListFilter;

final class CommentListFilterTest extends TestCase
{
    #[Test]
    public function toRepositoryFilterReturnsNullForAll(): void
    {
        self::assertNull(CommentListFilter::All->toRepositoryFilter());
    }

    #[Test]
    public function tryFromRequestMapsKnownFilters(): void
    {
        self::assertSame(CommentListFilter::Pending, CommentListFilter::tryFromRequest('pending'));
    }

    #[Test]
    public function tryFromRequestFallsBackToAllForUnknownFilter(): void
    {
        self::assertSame(CommentListFilter::All, CommentListFilter::tryFromRequest('unknown'));
    }
}
