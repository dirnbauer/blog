<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Enum;

/**
 * Comment list tabs in the backend moderation module.
 */
enum CommentListFilter: string
{
    case All = 'all';
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Deleted = 'deleted';

    public function toRepositoryFilter(): ?string
    {
        return $this === self::All ? null : $this->value;
    }

    public static function tryFromRequest(?string $filter): self
    {
        if ($filter === null || $filter === '') {
            return self::All;
        }

        return self::tryFrom($filter) ?? self::All;
    }

    /**
     * @return list<self>
     */
    public static function countableCases(): array
    {
        return [
            self::All,
            self::Pending,
            self::Approved,
            self::Declined,
            self::Deleted,
        ];
    }
}
