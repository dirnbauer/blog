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
use ReflectionClass;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Domain\Model\Category;
use T3G\AgencyPack\Blog\Domain\Model\Tag;
use T3G\AgencyPack\Blog\Service\RssFeedMetadataFactory;
use TYPO3\CMS\Extbase\Mvc\Request;

final class RssFeedMetadataFactoryTest extends TestCase
{
    private RssFeedMetadataFactory $subject;

    protected function setUp(): void
    {
        $this->subject = new RssFeedMetadataFactory();
    }

    #[Test]
    public function resolveTranslationArgumentsIncludesAuthorName(): void
    {
        $author = new Author();
        $author->setName('Jane Doe');

        $request = $this->createMock(Request::class);
        $request->method('getControllerActionName')->willReturn('listPostsByAuthor');
        $request->method('hasArgument')->with('author')->willReturn(true);
        $request->method('getArgument')->with('author')->willReturn($author);

        self::assertSame(['Jane Doe'], $this->resolveTranslationArguments($request));
    }

    #[Test]
    public function resolveTranslationArgumentsIncludesCategoryTitle(): void
    {
        $category = new Category();
        $category->setTitle('News');

        $request = $this->createMock(Request::class);
        $request->method('getControllerActionName')->willReturn('listPostsByCategory');
        $request->method('hasArgument')->with('category')->willReturn(true);
        $request->method('getArgument')->with('category')->willReturn($category);

        self::assertSame(['News'], $this->resolveTranslationArguments($request));
    }

    #[Test]
    public function resolveTranslationArgumentsIncludesTagTitle(): void
    {
        $tag = new Tag();
        $tag->setTitle('TYPO3');

        $request = $this->createMock(Request::class);
        $request->method('getControllerActionName')->willReturn('listPostsByTag');
        $request->method('hasArgument')->with('tag')->willReturn(true);
        $request->method('getArgument')->with('tag')->willReturn($tag);

        self::assertSame(['TYPO3'], $this->resolveTranslationArguments($request));
    }

    #[Test]
    public function resolveTranslationArgumentsIncludesYearAndMonth(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getControllerActionName')->willReturn('listPostsByDate');
        $request->method('hasArgument')->willReturnMap([
            ['year', true],
            ['month', true],
        ]);
        $request->method('getArgument')->willReturnMap([
            ['year', 2024],
            ['month', 6],
        ]);

        self::assertSame([2024, 6], $this->resolveTranslationArguments($request));
    }

    #[Test]
    public function resolveTranslationArgumentsReturnsEmptyListForUnknownAction(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getControllerActionName')->willReturn('listRecentPosts');

        self::assertSame([], $this->resolveTranslationArguments($request));
    }

    /**
     * @return list<int|string>
     */
    private function resolveTranslationArguments(Request $request): array
    {
        $method = (new ReflectionClass(RssFeedMetadataFactory::class))->getMethod('resolveTranslationArguments');

        $result = $method->invoke($this->subject, $request);
        self::assertIsArray($result);

        $arguments = [];
        foreach ($result as $value) {
            if (is_int($value) || is_string($value)) {
                $arguments[] = $value;
            }
        }

        return $arguments;
    }
}
