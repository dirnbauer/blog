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
use T3G\AgencyPack\Blog\Domain\Model\Comment;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CommentViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('comment', Comment::class, 'The comment to link to', true);
        $this->registerArgument('returnUri', 'bool', 'return only uri', false, false);
    }

    public function render(): string
    {
        $request = $this->getRequest();
        /** @var Comment $comment */
        $comment = $this->arguments['comment'];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $params = [
            'edit' => ['tx_blog_domain_model_comment' => [$comment->getUid() => 'edit']],
            'returnUrl' => RequestUtility::getRequestUri($request),
        ];
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
        $uri = self::normalizeBackendUri($uri);
        if (isset($this->arguments['returnUri']) && $this->arguments['returnUri'] === true) {
            return htmlspecialchars($uri, ENT_QUOTES | ENT_HTML5);
        }

        $linkText = TypeUtility::toString($this->renderChildren(), TypeUtility::toString($comment->getComment()));
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($linkText);

        return $this->tag->render();
    }

    private static function normalizeBackendUri(string $uri): string
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
}
