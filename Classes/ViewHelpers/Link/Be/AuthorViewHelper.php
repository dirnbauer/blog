<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ViewHelpers\Link\Be;

use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AuthorViewHelper extends AbstractBackendLinkViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('author', Author::class, 'The author to link to', true);
        $this->registerArgument('returnUri', 'bool', 'return only uri', false, false);
    }

    public function render(): string
    {
        $request = $this->getRequest();
        /** @var Author $author */
        $author = $this->arguments['author'];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => ['tx_blog_domain_model_author' => [$author->getUid() => 'edit']],
            'returnUrl' => RequestUtility::getRequestUri($request),
        ]);

        return $this->renderUriOrTag($uri, TypeUtility::toString($this->renderChildren(), TypeUtility::toString($author->getName())));
    }
}
