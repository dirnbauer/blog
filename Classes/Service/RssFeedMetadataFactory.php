<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Service;

use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Domain\Model\Category;
use T3G\AgencyPack\Blog\Domain\Model\Tag;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Builds RSS feed metadata for PostController list actions.
 */
final class RssFeedMetadataFactory
{
    /**
     * @return array{title: string|null, description: string|null, language: string, link: string, date: string}
     */
    public function build(Request $request, SiteLanguage $siteLanguage, string $requestUrl): array
    {
        $action = '.' . $request->getControllerActionName();
        $arguments = $this->resolveTranslationArguments($request);

        return [
            'title' => LocalizationUtility::translate('feed.title' . $action, 'blog', $arguments),
            'description' => LocalizationUtility::translate('feed.description' . $action, 'blog', $arguments),
            'language' => $siteLanguage->getLocale()->getLanguageCode(),
            'link' => $requestUrl,
            'date' => date('r'),
        ];
    }

    /**
     * @return list<int|string>
     */
    private function resolveTranslationArguments(Request $request): array
    {
        return match ('.' . $request->getControllerActionName()) {
            '.listPostsByCategory' => $this->categoryArguments($request),
            '.listPostsByDate' => $this->dateArguments($request),
            '.listPostsByTag' => $this->tagArguments($request),
            '.listPostsByAuthor' => $this->authorArguments($request),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function categoryArguments(Request $request): array
    {
        if (!$request->hasArgument('category')) {
            return [];
        }
        $category = $request->getArgument('category');

        return $category instanceof Category ? [$category->getTitle()] : [];
    }

    /**
     * @return list<int>
     */
    private function dateArguments(Request $request): array
    {
        $arguments = [];
        if ($request->hasArgument('year')) {
            $year = $request->getArgument('year');
            if (is_numeric($year)) {
                $arguments[] = (int)$year;
            }
        }
        if ($request->hasArgument('month')) {
            $month = $request->getArgument('month');
            if (is_numeric($month)) {
                $arguments[] = (int)$month;
            }
        }

        return $arguments;
    }

    /**
     * @return list<string>
     */
    private function tagArguments(Request $request): array
    {
        if (!$request->hasArgument('tag')) {
            return [];
        }
        $tag = $request->getArgument('tag');

        return $tag instanceof Tag ? [$tag->getTitle()] : [];
    }

    /**
     * @return list<string>
     */
    private function authorArguments(Request $request): array
    {
        if (!$request->hasArgument('author')) {
            return [];
        }
        $author = $request->getArgument('author');

        return $author instanceof Author ? [$author->getName()] : [];
    }
}
