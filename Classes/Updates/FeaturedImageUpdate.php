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
use TYPO3\CMS\Core\Upgrades\RepeatableInterface;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

#[UpgradeWizard(FeaturedImageUpdate::class)]
final class FeaturedImageUpdate extends AbstractUpdate implements UpgradeWizardInterface, RepeatableInterface
{
    protected string $title = 'updates.featured_image.title';

    public function updateNecessary(): bool
    {
        $records = $this->getAffectedRecords();
        return (bool) count($records);
    }

    public function executeUpdate(): bool
    {
        $records = $this->getAffectedRecords();
        foreach ($records as $record) {
            $this->updateRecord('sys_file_reference', TypeUtility::toInt($record['uid'] ?? null), [
                'fieldname' => 'featured_image',
            ]);
            $this->updateRecord('pages', TypeUtility::toInt($record['uid_foreign'] ?? null), [
                'featured_image' => 1,
                'media' => 0,
            ]);
        }

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAffectedPages(): array
    {
        $queryBuilder = $this->createQueryBuilder('pages');
        $criteria = [
            $this->createEqualIntCriteria($queryBuilder, 'doktype', Constants::DOKTYPE_BLOG_POST),
            $this->createEqualIntCriteria($queryBuilder, 'featured_image', 0),
            $this->createNotEqualIntCriteria($queryBuilder, 'media', 0),
        ];
        $records = $this->getRecordsByCriteria($queryBuilder, 'pages', $criteria);

        return $records;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAffectedRecords(): array
    {
        $pages = array_map(
            static fn (array $page): int => TypeUtility::toInt($page['uid'] ?? null),
            $this->getAffectedPages(),
        );

        $queryBuilder = $this->createQueryBuilder('sys_file_reference');
        $criteria = [
            $this->createEqualStringCriteria($queryBuilder, 'tablenames', 'pages'),
            $this->createEqualStringCriteria($queryBuilder, 'fieldname', 'media'),
            $this->createInCriteria($queryBuilder, 'uid_foreign', $pages),
        ];
        $records = $this->getRecordsByCriteria($queryBuilder, 'sys_file_reference', $criteria);

        return $records;
    }
}
