<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;

final class RequestUtility
{
    public static function getGlobalRequest(): ServerRequestInterface
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            throw new \RuntimeException('No TYPO3 server request is available.', 1744301001);
        }

        return $request;
    }

    public static function getNormalizedParams(ServerRequestInterface $request): NormalizedParams
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            throw new \RuntimeException('The current TYPO3 request has no normalized params.', 1744301002);
        }

        return $normalizedParams;
    }

    public static function getSite(ServerRequestInterface $request): ?Site
    {
        $site = $request->getAttribute('site');

        return $site instanceof Site ? $site : null;
    }

    public static function getSiteSettings(ServerRequestInterface $request): SiteSettings
    {
        $site = self::getSite($request);
        if (!$site instanceof Site) {
            throw new \RuntimeException('The current TYPO3 request has no site configuration.', 1744301003);
        }

        return $site->getSettings();
    }

    public static function getSiteLanguage(ServerRequestInterface $request): SiteLanguage
    {
        $siteLanguage = $request->getAttribute('language');
        if (!$siteLanguage instanceof SiteLanguage) {
            throw new \RuntimeException('The current TYPO3 request has no site language.', 1744301004);
        }

        return $siteLanguage;
    }

    public static function getPageInformation(ServerRequestInterface $request): ?PageInformation
    {
        $pageInformation = $request->getAttribute('frontend.page.information');

        return $pageInformation instanceof PageInformation ? $pageInformation : null;
    }

    public static function getFrontendTypoScript(ServerRequestInterface $request): ?FrontendTypoScript
    {
        $frontendTypoScript = $request->getAttribute('frontend.typoscript');

        return $frontendTypoScript instanceof FrontendTypoScript ? $frontendTypoScript : null;
    }

    public static function getCurrentContentObject(ServerRequestInterface $request): ?ContentObjectRenderer
    {
        $contentObject = $request->getAttribute('currentContentObject');

        return $contentObject instanceof ContentObjectRenderer ? $contentObject : null;
    }

    public static function getRequestUri(ServerRequestInterface $request): string
    {
        return self::getNormalizedParams($request)->getRequestUri();
    }

    public static function getSiteSettingString(ServerRequestInterface $request, string $settingPath, string $default = ''): string
    {
        return TypeUtility::toString(self::getSiteSettings($request)->get($settingPath), $default);
    }

    public static function getSiteSettingInt(ServerRequestInterface $request, string $settingPath, int $default = 0): int
    {
        return TypeUtility::toInt(self::getSiteSettings($request)->get($settingPath), $default);
    }

    public static function getSiteSettingBool(ServerRequestInterface $request, string $settingPath, bool $default = false): bool
    {
        return TypeUtility::toBool(self::getSiteSettings($request)->get($settingPath), $default);
    }

    public static function getTypoScriptTypeNum(ServerRequestInterface $request, string $setupName): int
    {
        $frontendTypoScript = self::getFrontendTypoScript($request);
        if (!$frontendTypoScript instanceof FrontendTypoScript) {
            return 0;
        }

        return TypeUtility::toInt(
            $frontendTypoScript->getSetupTree()
                ->getChildByName($setupName)
                ?->getChildByName('typeNum')
                ?->getValue(),
        );
    }
}
