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
use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupSummary;
use T3G\AgencyPack\Blog\Service\BackendBlogContextService;

final class BackendBlogContextServiceTest extends TestCase
{
    private BackendBlogContextService $subject;

    /** @var list<BlogSetupSummary> */
    private array $setups;

    protected function setUp(): void
    {
        $this->subject = new BackendBlogContextService();
        $this->setups = [
            new BlogSetupSummary(10, 'Blog A', 'Site / Blog A', 3),
            new BlogSetupSummary(20, 'Blog B', 'Site / Blog B', 5),
        ];
    }

    #[Test]
    public function getAccessibleIdsReturnsUids(): void
    {
        self::assertSame([10, 20], $this->subject->getAccessibleIds($this->setups));
    }

    #[Test]
    public function resolveActiveBlogSetupReturnsRequestedIdWhenAccessible(): void
    {
        self::assertSame(20, $this->subject->resolveActiveBlogSetup(20, $this->setups));
    }

    #[Test]
    public function resolveActiveBlogSetupReturnsNullWhenNotAccessible(): void
    {
        self::assertNull($this->subject->resolveActiveBlogSetup(99, $this->setups));
    }

    #[Test]
    public function resolveActiveBlogSetupReturnsNullWhenNotRequested(): void
    {
        self::assertNull($this->subject->resolveActiveBlogSetup(null, $this->setups));
    }
}
