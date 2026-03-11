<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\ExpressionLanguage;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\ExpressionLanguage\BlogVariableProvider;
use TYPO3\CMS\Core\Routing\PageInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BlogVariableProviderTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function isPostReturnsTrueForBlogPost(): void
    {
        $this->setUpRequest(Constants::DOKTYPE_BLOG_POST);
        $provider = new BlogVariableProvider();
        self::assertTrue($provider->isPost());
    }

    #[Test]
    public function isPostReturnsFalseForNonBlogPage(): void
    {
        $this->setUpRequest(1);
        $provider = new BlogVariableProvider();
        self::assertFalse($provider->isPost());
    }

    #[Test]
    public function isPageReturnsTrueForBlogPage(): void
    {
        $this->setUpRequest(Constants::DOKTYPE_BLOG_PAGE);
        $provider = new BlogVariableProvider();
        self::assertTrue($provider->isPage());
    }

    #[Test]
    public function isPageReturnsFalseForNonBlogPage(): void
    {
        $this->setUpRequest(Constants::DOKTYPE_BLOG_POST);
        $provider = new BlogVariableProvider();
        self::assertFalse($provider->isPage());
    }

    #[Test]
    public function isPostReturnsFalseWithoutRequest(): void
    {
        $provider = new BlogVariableProvider();
        self::assertFalse($provider->isPost());
    }

    #[Test]
    public function isPostReturnsFalseWithoutPageInformation(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('frontend.page.information')->willReturn(null);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $provider = new BlogVariableProvider();
        self::assertFalse($provider->isPost());
    }

    private function setUpRequest(int $doktype): void
    {
        $pageInformation = $this->createMock(PageInformation::class);
        $pageInformation->method('getPageRecord')->willReturn(['doktype' => $doktype]);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('frontend.page.information')->willReturn($pageInformation);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }
}
