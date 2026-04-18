<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ExpressionLanguage;

use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\Utility\RequestUtility;

class BlogVariableProvider
{
    public function isPost(): bool
    {
        return $this->getCurrentDoktype() === Constants::DOKTYPE_BLOG_POST;
    }

    public function isPage(): bool
    {
        return $this->getCurrentDoktype() === Constants::DOKTYPE_BLOG_PAGE;
    }

    private function getCurrentDoktype(): int
    {
        try {
            $request = RequestUtility::getGlobalRequest();
        } catch (\RuntimeException) {
            return 0;
        }

        $pageInformation = RequestUtility::getPageInformation($request);
        if ($pageInformation === null) {
            return 0;
        }

        $pageRecord = $pageInformation->getPageRecord();
        return (int)($pageRecord['doktype'] ?? 0);
    }
}
