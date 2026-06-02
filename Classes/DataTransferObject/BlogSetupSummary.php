<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\DataTransferObject;

/**
 * Backend module representation of a blog root page (sysfolder with module=blog).
 */
final readonly class BlogSetupSummary
{
    /**
     * @param list<array<string, mixed>> $rootline
     */
    public function __construct(
        public int $uid,
        public string $title,
        public string $path,
        public int $articleCount,
        public array $rootline = [],
    ) {
    }

    /**
     * @param array{uid?: int|string, title?: string, path?: string, articleCount?: int|string, rootline?: list<array<string, mixed>>} $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            uid: (int)($row['uid'] ?? 0),
            title: (string)($row['title'] ?? ''),
            path: (string)($row['path'] ?? ''),
            articleCount: (int)($row['articleCount'] ?? 0),
            rootline: $row['rootline'] ?? [],
        );
    }

    /**
     * @return array{uid: int, title: string, path: string, articleCount: int, rootline: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'title' => $this->title,
            'path' => $this->path,
            'articleCount' => $this->articleCount,
            'rootline' => $this->rootline,
        ];
    }
}
