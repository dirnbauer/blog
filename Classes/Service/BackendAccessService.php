<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\Domain\Model\Comment;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

final class BackendAccessService
{
    public function getBackendUser(): ?BackendUserAuthentication
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        return $backendUser instanceof BackendUserAuthentication ? $backendUser : null;
    }

    public function canReadTable(string $table): bool
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            return false;
        }

        return $backendUser->check('tables_select', $table) || $backendUser->check('tables_modify', $table);
    }

    public function canModifyTable(string $table): bool
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            return false;
        }

        return $backendUser->check('tables_modify', $table);
    }

    public function canViewBlogRoot(int $pageUid): bool
    {
        return $this->hasPagePermission($pageUid, Permission::PAGE_SHOW);
    }

    public function canModerateComment(Comment $comment): bool
    {
        if (!$this->canModifyTable('tx_blog_domain_model_comment')) {
            return false;
        }

        return $this->hasPagePermission((int)$comment->getPid(), Permission::CONTENT_EDIT);
    }

    public function filterAccessibleBlogSetups(array $blogSetups): array
    {
        if (!$this->getBackendUser() instanceof BackendUserAuthentication) {
            return $blogSetups;
        }

        foreach ($blogSetups as $key => $blogSetup) {
            $pageUid = (int)($blogSetup['uid'] ?? 0);
            if (!$this->canViewBlogRoot($pageUid)) {
                unset($blogSetups[$key]);
            }
        }

        return $blogSetups;
    }

    protected function hasPagePermission(int $pageUid, int $permission): bool
    {
        if ($pageUid <= 0) {
            return false;
        }

        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            return false;
        }

        return is_array(BackendUtility::readPageAccess($pageUid, $backendUser->getPagePermsClause($permission)));
    }
}
