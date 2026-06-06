<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Functional\Helpers;

use T3G\AgencyPack\Blog\Domain\Repository\AuthorRepository;
use T3G\AgencyPack\Blog\Domain\Repository\CategoryRepository;
use T3G\AgencyPack\Blog\Domain\Repository\CommentRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use T3G\AgencyPack\Blog\Domain\Repository\TagRepository;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class TestDataProcessor implements DataProcessorInterface
{
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        $authorRepository = GeneralUtility::makeInstance(AuthorRepository::class);
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $commentRepository = GeneralUtility::makeInstance(CommentRepository::class);
        $postRepository = GeneralUtility::makeInstance(PostRepository::class);
        $tagRepository = GeneralUtility::makeInstance(TagRepository::class);

        $dataConfig = $processorConfiguration['data.'] ?? null;
        if (!is_iterable($dataConfig)) {
            return $processedData;
        }

        $result = [];
        foreach ($dataConfig as $config) {
            if (!is_array($config)) {
                continue;
            }
            $as = TypeUtility::toString($config['as'] ?? null);
            $uid = TypeUtility::toInt($config['uid'] ?? null);
            switch ($config['type'] ?? null) {
                case 'author':
                    $result[$as] = $authorRepository->findByUid($uid);
                    break;
                case 'category':
                    $result[$as] = $categoryRepository->findByUid($uid);
                    break;
                case 'comment':
                    $result[$as] = $commentRepository->findByUid($uid);
                    break;
                case 'post':
                    $result[$as] = $postRepository->findByUid($uid);
                    break;
                case 'tag':
                    $result[$as] = $tagRepository->findByUid($uid);
                    break;
            }
        }

        $processedData['test'] = $result;
        return $processedData;
    }
}
