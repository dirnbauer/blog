<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\Blog\Utility\DataHandlerUidReplacer;

final class DataHandlerUidReplacerTest extends TestCase
{
    private DataHandlerUidReplacer $subject;

    protected function setUp(): void
    {
        $this->subject = new DataHandlerUidReplacer();
    }

    #[Test]
    public function replaceSubstitutesKeysAndStringValues(): void
    {
        $input = [
            'NEW_blogPage' => [
                'pid' => 'NEW_blogRoot',
                'title' => 'Archive',
            ],
        ];
        $result = $this->subject->replace($input, [
            'NEW_blogRoot' => 42,
            'NEW_blogPage' => 99,
        ]);

        self::assertArrayHasKey('99', $result);
        self::assertSame('42', $result['99']['pid']);
    }

    #[Test]
    public function replaceLeavesUnrelatedPlaceholdersUntouched(): void
    {
        $result = $this->subject->replace(['NEW_unknown' => 'value'], ['NEW_blogRoot' => 1]);

        self::assertArrayHasKey('NEW_unknown', $result);
    }
}
