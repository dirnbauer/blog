<?php

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Blog Extension',
    'description' => 'A blog for TYPO3 built on core concepts and content elements.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'TYPO3 GmbH',
    'author_email' => 'info@typo3.com',
    'version' => '14.3.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.4.99',
            'typo3' => '14.0.0-14.99.99',
            'form' => '14.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
