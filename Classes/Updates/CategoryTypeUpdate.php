<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Updates;

use T3G\AgencyPack\Blog\Constants;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

#[UpgradeWizard(CategoryTypeUpdate::class)]
final class CategoryTypeUpdate extends AbstractUpdate implements UpgradeWizardInterface
{
    protected string $title = 'updates.category_type.title';
    protected string $table = 'sys_category';

    public function updateNecessary(): bool
    {
        $records = $this->getAffectedRecords();
        return (bool) count($records);
    }

    /**
     */
    public function executeUpdate(): bool
    {
        $records = $this->getAffectedRecords();
        foreach ($records as $record) {
            $this->updateRecord($this->table, TypeUtility::toInt($record['uid'] ?? null), [
                'record_type' => Constants::CATEGORY_TYPE_BLOG,
            ]);
        }

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAffectedRecords(): array
    {
        $pages = array_map(
            static fn (array $page): int => TypeUtility::toInt($page['uid'] ?? null),
            $this->getBlogStorageFolders(),
        );

        $queryBuilder = $this->createQueryBuilder($this->table);
        $criteria = [
            $this->createEqualIntCriteria($queryBuilder, 'record_type', Constants::CATEGORY_TYPE_DEFAULT),
            $this->createInCriteria($queryBuilder, 'pid', $pages),
        ];
        $records = $this->getRecordsByCriteria($queryBuilder, $this->table, $criteria);

        return $records;
    }
}
