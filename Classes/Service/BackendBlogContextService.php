<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\DataTransferObject\BlogSetupSummary;

/**
 * Resolves accessible blog setups and the active blog root for backend modules.
 */
final class BackendBlogContextService
{
    public function __construct(
        private readonly SetupService $setupService,
    ) {
    }

    /**
     * @return list<BlogSetupSummary>
     */
    public function getAccessibleSetups(): array
    {
        return $this->setupService->determineBlogSetups();
    }

    /**
     * @param list<BlogSetupSummary> $setups
     *
     * @return list<int>
     */
    public function getAccessibleIds(array $setups): array
    {
        return array_values(array_filter(array_map(
            static fn (BlogSetupSummary $setup): int => $setup->uid,
            $setups,
        ), static fn (int $uid): bool => $uid > 0));
    }

    /**
     * @param list<BlogSetupSummary> $setups
     */
    public function resolveActiveBlogSetup(?int $requestedId, array $setups): ?int
    {
        $accessibleIds = $this->getAccessibleIds($setups);
        if ($requestedId !== null && in_array($requestedId, $accessibleIds, true)) {
            return $requestedId;
        }

        return null;
    }
}
