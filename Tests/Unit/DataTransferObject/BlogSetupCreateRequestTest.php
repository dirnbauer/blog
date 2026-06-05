<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\DataTransferObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupCreateRequest;

final class BlogSetupCreateRequestTest extends TestCase
{
    #[Test]
    public function fromRequestDataReturnsNullForMissingPayload(): void
    {
        self::assertNull(BlogSetupCreateRequest::fromRequestData(null));
    }

    #[Test]
    public function toSetupDataOmitsEmptyTitle(): void
    {
        self::assertSame([], (new BlogSetupCreateRequest())->toSetupData());
    }

    #[Test]
    public function toSetupDataIncludesTitle(): void
    {
        self::assertSame(
            ['title' => 'Demo Blog'],
            (new BlogSetupCreateRequest(title: 'Demo Blog'))->toSetupData(),
        );
    }
}
