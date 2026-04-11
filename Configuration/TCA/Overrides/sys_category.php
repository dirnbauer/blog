<?php

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

$ll = 'LLL:EXT:blog/Resources/Private/Language/locallang_db.xlf:';
$sysCategoryTca = \T3G\AgencyPack\Blog\Utility\TcaUtility::getTableTca('sys_category');
$typeIconClasses = \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedArray($sysCategoryTca, ['ctrl', 'typeicon_classes']);
$typeIconClasses[(string)\T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_BLOG] = 'record-blog-category';
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['ctrl', 'type'], 'record_type');
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['ctrl', 'typeicon_column'], 'record_type');
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['ctrl', 'typeicon_classes'], $typeIconClasses);

$defaultTypeIcon = \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedString(
    $sysCategoryTca,
    ['ctrl', 'typeicon_classes', 'default']
);
$columns = \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedArray($sysCategoryTca, ['columns']);

// Add category types
$columns['record_type'] = [
    'label' => $ll . 'sys_category.record_type',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        'items' => [
            [
                'label' => 'LLL:EXT:blog/Resources/Private/Language/locallang_tca.xlf:sys_category.record_type.default',
                'value' => (string) \T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_DEFAULT,
                'icon' => $defaultTypeIcon
            ],
            [
                'label' => 'LLL:EXT:blog/Resources/Private/Language/locallang_tca.xlf:sys_category.record_type.blog',
                'value' => (string) \T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_BLOG,
                'icon' => 'record-blog-category'
            ]
        ],
        'default' => (string) \T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_DEFAULT
    ]
];
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['columns'], $columns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_category',
    'record_type',
    '',
    'before:title'
);
$types = \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedArray($sysCategoryTca, ['types']);
$types[(string)\T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_BLOG] =
    \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedArray(
        $sysCategoryTca,
        ['types', (string)\T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_DEFAULT]
    );
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['types'], $types);

// Limit parent categories to blog types
$pagesCategoryWhere = \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedString(
    \T3G\AgencyPack\Blog\Utility\TcaUtility::getTableTca('pages'),
    ['columns', 'categories', 'config', 'foreign_table_where']
);
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['types', \T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_BLOG, 'columnsOverrides'], [
    'parent' => [
        'config' => [
            'foreign_table_where' => '' .
                ' AND sys_category.record_type = ' . (string) \T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_BLOG . ' ' .
                ' AND sys_category.pid = ###CURRENT_PID### ' .
                $pagesCategoryWhere
        ]
    ]
]);

// Register fields
$columns = array_replace_recursive(
    \T3G\AgencyPack\Blog\Utility\TcaUtility::getNestedArray($sysCategoryTca, ['columns']),
    [
        'slug' => [
            'label' => $ll . 'sys_category.slug',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'replacements' => [
                        '/' => ''
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => ''
            ]
        ],
        'content' => [
            'label' => $ll . 'sys_category.content',
            'config' => [
                'type' => 'inline',
                'allowed' => 'tt_content',
                'foreign_table' => 'tt_content',
                'foreign_sortby' => 'sorting',
                'foreign_field' => 'tx_blog_category_content',
                'minitems' => 0,
                'maxitems' => 99,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'showSynchronizationLink' => 1,
                    'enabledControls' => [
                        'info' => false,
                    ],
                ],
                'richtextConfiguration' => 'default'
            ],
        ],
        'posts' => [
            'label' => $ll . 'sys_category.posts',
            'config' => [
                'type' => 'group',
                'size' => 5,
                'allowed' => 'pages',
                'foreign_table' => 'pages',
                'MM' => 'sys_category_record_mm',
                'MM_match_fields' => [
                    'fieldname' => 'categories',
                    'tablenames' => 'pages',
                ],
                'maxitems' => 1000
            ],
        ],
    ]
);
\T3G\AgencyPack\Blog\Utility\TcaUtility::setNestedValue($sysCategoryTca, ['columns'], $columns);
\T3G\AgencyPack\Blog\Utility\TcaUtility::setTableTca('sys_category', $sysCategoryTca);

// Add slug field to all types
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_category',
    'slug',
    '',
    'after:title'
);

// Add blog specific fields to blog categories
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_category',
    '
        --div--;' . $ll . 'sys_category.tabs.seo,
            content,
        --div--;' . $ll . 'sys_category.tabs.blog,
            posts
    ',
    (string) \T3G\AgencyPack\Blog\Constants::CATEGORY_TYPE_BLOG
);
