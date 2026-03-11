<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\ViewHelpers\Format;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use T3G\AgencyPack\Blog\ViewHelpers\Format\Nl2pViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

final class Nl2pViewHelperTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('renderDataProvider')]
    public function render(string $input, string $expected): void
    {
        $viewHelper = new Nl2pViewHelper();
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $viewHelper->setRenderingContext($renderingContext);
        $viewHelper->setRenderChildrenClosure(static fn () => $input);
        self::assertSame($expected, $viewHelper->render());
    }

    public static function renderDataProvider(): \Generator
    {
        yield 'plain text' => [
            'Hello World',
            '<p>Hello World</p>',
        ];

        yield 'text with newline' => [
            "Line one\nLine two",
            '<p>Line one</p><p>Line two</p>',
        ];

        yield 'text with multiple newlines' => [
            "First\n\nSecond\nThird",
            '<p>First</p><p>Second</p><p>Third</p>',
        ];

        yield 'XSS attempt with script tag' => [
            '<script>alert("xss")</script>',
            '<p>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</p>',
        ];

        yield 'XSS attempt with event handler' => [
            '<img onerror="alert(1)" src=x>',
            '<p>&lt;img onerror=&quot;alert(1)&quot; src=x&gt;</p>',
        ];

        yield 'HTML entities are escaped' => [
            'Tom & Jerry <3',
            '<p>Tom &amp; Jerry &lt;3</p>',
        ];

        yield 'empty string' => [
            '',
            '<p></p>',
        ];
    }
}
