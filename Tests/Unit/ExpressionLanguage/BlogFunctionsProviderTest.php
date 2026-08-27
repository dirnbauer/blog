<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\ExpressionLanguage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\ExpressionLanguage\BlogFunctionsProvider;

final class BlogFunctionsProviderTest extends TestCase
{
    public function testFunctionsMatchBlogDoktypes(): void
    {
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->registerProvider(new BlogFunctionsProvider());

        self::assertTrue($expressionLanguage->evaluate('isBlogPost()', [
            'page' => ['doktype' => Constants::DOKTYPE_BLOG_POST],
        ]));
        self::assertTrue($expressionLanguage->evaluate('isBlogPage()', [
            'page' => ['doktype' => Constants::DOKTYPE_BLOG_PAGE],
        ]));
        self::assertFalse($expressionLanguage->evaluate('isBlogPost()', [
            'page' => ['doktype' => Constants::DOKTYPE_BLOG_PAGE],
        ]));
    }
}
