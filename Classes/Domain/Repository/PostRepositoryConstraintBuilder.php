<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Domain\Repository;

use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Builds default Extbase query constraints for blog post (pages) records.
 */
final class PostRepositoryConstraintBuilder
{
    public function __construct(
        private readonly Context $context,
    ) {
    }

    /**
     * @return list<ConstraintInterface>
     */
    public function buildDefaultConstraints(QueryInterface $query): array
    {
        $constraints = [
            $query->equals('doktype', Constants::DOKTYPE_BLOG_POST),
        ];

        if ($this->context->getAspect('language')->getId() === 0) {
            $constraints[] = $query->logicalOr(
                $query->equals('l18n_cfg', 0),
                $query->equals('l18n_cfg', 2),
            );
        } else {
            $constraints[] = $query->lessThan('l18n_cfg', 2);
        }

        if ($this->isBackendRequest()) {
            $workspaceId = TypeUtility::toInt($this->context->getPropertyFromAspect('workspace', 'id', 0));
            if ($workspaceId === 0) {
                $constraints[] = $query->equals('t3ver_wsid', 0);
            } else {
                $constraints[] = $query->logicalOr(
                    $query->equals('t3ver_wsid', 0),
                    $query->equals('t3ver_wsid', $workspaceId),
                );
            }
        }

        return $constraints;
    }

    public function isBackendRequest(): bool
    {
        try {
            return ApplicationType::fromRequest(RequestUtility::getGlobalRequest())->isBackend();
        } catch (\RuntimeException) {
            return false;
        }
    }
}
