<?php

declare(strict_types=1);

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

return [
    'ctrl' => [
        'title' => $ll . 'tx_blog_domain_model_comment',
        'label' => 'name',
        'label_alt' => 'crdate',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate DESC',
        'delete' => 'deleted',
        // Comments are submitted by the frontend public and moderated live;
        // intentionally not workspace-versioned. `versioningWS_alwaysAllowLiveEdit`
        // documents the intent + keeps edits in LIVE when a workspace is active.
        'versioningWS_alwaysAllowLiveEdit' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'record-blog-comment',
        ],
        'searchFields' => 'comment,name,email',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'pid' => [
            'label' => 'pid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'name' => [
            'label' => $ll . 'tx_blog_domain_model_comment.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'searchable' => true,
            ],
        ],
        'url' => [
            'label' => $ll . 'tx_blog_domain_model_comment.url',
            'config' => [
                'type' => 'link',
                'size' => 30,
                'allowedTypes' => ['url'],
            ],
        ],
        'email' => [
            'label' => $ll . 'tx_blog_domain_model_comment.email',
            'config' => [
                'type' => 'email',
                'size' => 30,
                'searchable' => true,
            ],
        ],
        'comment' => [
            'label' => $ll . 'tx_blog_domain_model_comment.comment',
            'config' => [
                'type' => 'text',
                'size' => 30,
                'searchable' => true,
            ],
        ],
        'post_language_id' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'status' => [
            'label' => $ll . 'tx_blog_domain_model_comment.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => $ll . 'tx_blog_domain_model_comment.status.pending', 'value' => \T3G\AgencyPack\Blog\Domain\Model\Comment::STATUS_PENDING],
                    ['label' => $ll . 'tx_blog_domain_model_comment.status.approved', 'value' => \T3G\AgencyPack\Blog\Domain\Model\Comment::STATUS_APPROVED],
                    ['label' => $ll . 'tx_blog_domain_model_comment.status.declined', 'value' => \T3G\AgencyPack\Blog\Domain\Model\Comment::STATUS_DECLINED],
                    ['label' => $ll . 'tx_blog_domain_model_comment.status.deleted', 'value' => \T3G\AgencyPack\Blog\Domain\Model\Comment::STATUS_DELETED],
                ],
            ],
        ],
        'parentid' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'pages',
                'foreign_table_where' => ' AND doktype = ' . \T3G\AgencyPack\Blog\Constants::DOKTYPE_BLOG_POST,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hp' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        0 => [
            'showitem' => 'post_language_id,status,name,url,email,comment,post',
        ],
    ],
    'palettes' => [
        'paletteCore' => [
            'showitem' => 'hidden,',
        ],
    ],
];
