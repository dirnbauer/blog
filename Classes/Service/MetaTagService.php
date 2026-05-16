<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\TitleTagProvider\BlogTitleTagProvider;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;

final class MetaTagService
{
    public const META_TITLE = 'title';
    public const META_DESCRIPTION = 'description';

    public function __construct(
        private readonly BlogTitleTagProvider $titleTagProvider,
        private readonly MetaTagManagerRegistry $metaTagManagerRegistry,
    ) {
    }

    public function set(string $type, string $value): void
    {
        match ($type) {
            self::META_TITLE => $this->setTitle($value),
            self::META_DESCRIPTION => $this->setDescription($value),
            default => throw new \InvalidArgumentException('The type "' . $type . '" is not supported.', 1562020008),
        };
    }

    private function setTitle(string $value): void
    {
        $this->titleTagProvider->setTitle($value);
        $this->metaTagManagerRegistry->getManagerForProperty('og:title')->addProperty('og:title', $value);
        $this->metaTagManagerRegistry->getManagerForProperty('twitter:title')->addProperty('twitter:title', $value);
    }

    private function setDescription(string $value): void
    {
        $this->metaTagManagerRegistry->getManagerForProperty('description')->addProperty('description', $value);
        $this->metaTagManagerRegistry->getManagerForProperty('og:description')->addProperty('og:description', $value);
        $this->metaTagManagerRegistry->getManagerForProperty('twitter:description')->addProperty('twitter:description', $value);
    }
}
