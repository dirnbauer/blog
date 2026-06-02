<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ViewHelpers\Link\Be;

use T3G\AgencyPack\Blog\Domain\Model\Post;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class PostViewHelper extends AbstractBackendLinkViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('post', Post::class, 'The post to link to', true);
        $this->registerArgument('returnUri', 'bool', 'return only uri', false, false);
        $this->registerArgument('action', 'string', 'action to link', false, null);
    }

    public function render(): string
    {
        $request = $this->getRequest();
        /** @var Post $post */
        $post = $this->arguments['post'];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $uri = match ($this->arguments['action']) {
            'edit' => (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['pages' => [$post->getUid() => 'edit']],
                'returnUrl' => RequestUtility::getRequestUri($request),
            ]),
            default => (string)$uriBuilder->buildUriFromRoute('web_layout', [
                'id' => $post->getUid(),
            ]),
        };

        $linkText = TypeUtility::toString(
            $this->renderChildren(),
            $post->getTitle() !== ''
                ? $post->getTitle()
                : TypeUtility::toString(LocalizationUtility::translate('backend.message.nopost', 'blog')),
        );

        return $this->renderUriOrTag($uri, $linkText);
    }
}
