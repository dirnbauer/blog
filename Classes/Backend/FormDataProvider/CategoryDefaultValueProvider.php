<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Backend\FormDataProvider;

use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

class CategoryDefaultValueProvider implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $parentPageRow = TypeUtility::toArray($result['parentPageRow'] ?? null);
        if (($result['command'] ?? null) !== 'new' ||
            ($result['tableName'] ?? null) !== 'sys_category' ||
            ($parentPageRow['module'] ?? null) !== 'blog') {
            return $result;
        }

        $databaseRow = TypeUtility::toArray($result['databaseRow'] ?? null);
        $recordType = TypeUtility::toArray($databaseRow['record_type'] ?? null);
        $recordType[0] = (string) Constants::CATEGORY_TYPE_BLOG;
        $databaseRow['record_type'] = $recordType;
        $result['databaseRow'] = $databaseRow;
        $result['recordTypeValue'] = (string) Constants::CATEGORY_TYPE_BLOG;

        return $result;
    }
}
