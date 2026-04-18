<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CommentTest extends UnitTestCase
{
    #[Test]
    public function setUrlKeepsHttpAndHttpsUrls(): void
    {
        $comment = new Comment();
        $comment->setUrl('https://example.org/profile');
        self::assertSame('https://example.org/profile', $comment->getUrl());

        $comment->setUrl('http://example.org/profile');
        self::assertSame('http://example.org/profile', $comment->getUrl());
    }

    #[Test]
    public function setUrlNormalizesUrlWithoutSchemeToHttps(): void
    {
        $comment = new Comment();
        $comment->setUrl('example.org/profile');
        self::assertSame('https://example.org/profile', $comment->getUrl());
    }

    #[Test]
    public function setUrlClearsUnsupportedSchemes(): void
    {
        $comment = new Comment();
        $comment->setUrl('javascript:alert(1)');
        self::assertSame('', $comment->getUrl());
    }
}
