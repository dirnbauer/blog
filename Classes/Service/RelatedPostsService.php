<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class RelatedPostsService
{
    public function __construct(
        private readonly PostRepository $postRepository,
    ) {
    }

    /**
     * @return ObjectStorage<Post>
     */
    public function findRelatedPosts(int $categoryMultiplier = 1, int $tagMultiplier = 1, int $limit = 5): ObjectStorage
    {
        if ($categoryMultiplier === 0 && $tagMultiplier === 0) {
            $categoryMultiplier = 1;
        }

        /** @var ObjectStorage<Post> $posts */
        $posts = new ObjectStorage();
        $currentPost = $this->postRepository->findCurrentPost();
        if (!$currentPost instanceof Post) {
            return $posts;
        }

        $scores = [];
        foreach ($currentPost->getCategories() as $category) {
            foreach ($this->postRepository->findAllByCategory($category) as $related) {
                if (!$related instanceof Post || $related->getUid() === $currentPost->getUid()) {
                    continue;
                }
                $uid = (int) $related->getUid();
                $scores[$uid] = ($scores[$uid] ?? 0) + $categoryMultiplier;
            }
        }

        foreach ($currentPost->getTags() as $tag) {
            foreach ($this->postRepository->findAllByTag($tag) as $related) {
                if (!$related instanceof Post || $related->getUid() === $currentPost->getUid()) {
                    continue;
                }
                $uid = (int) $related->getUid();
                $scores[$uid] = ($scores[$uid] ?? 0) + $tagMultiplier;
            }
        }

        arsort($scores);
        $count = 0;
        foreach (array_keys($scores) as $uid) {
            if ($count === $limit) {
                break;
            }
            $post = $this->postRepository->findByUid($uid);
            if (!$post instanceof Post) {
                continue;
            }
            $posts->attach($post);
            $count++;
        }

        return $posts;
    }
}
