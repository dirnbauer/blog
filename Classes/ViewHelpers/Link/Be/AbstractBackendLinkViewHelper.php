<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ViewHelpers\Link\Be;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

abstract class AbstractBackendLinkViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    protected function normalizeBackendUri(string $uri): string
    {
        if ($uri !== '' && $uri[0] !== '/' && str_starts_with($uri, 'typo3/')) {
            return '/' . $uri;
        }

        return $uri;
    }

    protected function getRequest(): ServerRequestInterface
    {
        return RequestUtility::getGlobalRequest();
    }

    protected function renderUriOrTag(string $uri, string $linkText): string
    {
        $uri = $this->normalizeBackendUri($uri);
        if (isset($this->arguments['returnUri']) && $this->arguments['returnUri'] === true) {
            return htmlspecialchars($uri, ENT_QUOTES | ENT_HTML5);
        }

        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($linkText);

        return $this->tag->render();
    }
}
