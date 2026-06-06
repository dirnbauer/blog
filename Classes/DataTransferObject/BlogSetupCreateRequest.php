<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\DataTransferObject;

use T3G\AgencyPack\Blog\Utility\TypeUtility;

/**
 * Input for the backend blog setup wizard create action.
 */
final readonly class BlogSetupCreateRequest
{
    public function __construct(
        public ?string $title = null,
    ) {
    }

    /**
     * @param array<array-key, mixed>|null $data
     */
    public static function fromRequestData(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        $title = array_key_exists('title', $data) ? TypeUtility::toString($data['title']) : null;

        return new self(title: $title !== '' ? $title : null);
    }

    /**
     * @return array{title?: string}
     */
    public function toSetupData(): array
    {
        if ($this->title === null) {
            return [];
        }

        return ['title' => $this->title];
    }
}
