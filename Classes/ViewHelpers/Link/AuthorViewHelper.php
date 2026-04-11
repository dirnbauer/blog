<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class AuthorViewHelper extends AbstractTagBasedViewHelper
{
    public function __construct()
    {
        $this->tagName = 'a';
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('author', Author::class, 'The author to link to', true);
        $this->registerArgument('rss', 'bool', 'Link to rss version', false, false);
    }

    public function render(): string
    {
        /** @var Author $author */
        $author = $this->arguments['author'];
        $rssFormat = (bool)$this->arguments['rss'];

        if ((int)$author->getDetailsPage() > 0 && !$rssFormat) {
            return $this->buildUriFromDetailsPage($author, $rssFormat);
        }

        return $this->buildUriFromDefaultPage($author, $rssFormat);
    }

    protected function buildUriFromDetailsPage(Author $author, bool $rssFormat): string
    {
        $uriBuilder = $this->getUriBuilder((int) $author->getDetailsPage(), [], $rssFormat);
        return $this->buildAnchorTag($uriBuilder->build(), $author);
    }

    protected function buildUriFromDefaultPage(Author $author, bool $rssFormat): string
    {
        $request = $this->getRequest();
        $pageUid = RequestUtility::getSiteSettingInt($request, 'plugin.tx_blog.settings.authorUid');
        $uriBuilder = $this->getUriBuilder($pageUid, [], $rssFormat);
        $arguments = [
            'author' => $author->getUid(),
        ];
        return $this->buildAnchorTag($uriBuilder->uriFor('listPostsByAuthor', $arguments, 'Post', 'Blog', 'AuthorPosts'), $author);
    }

    protected function getUriBuilder(int $pageUid, array $additionalParams, bool $rssFormat): UriBuilder
    {
        $request = $this->getRequest();
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset()
            ->setRequest($request)
            ->setTargetPageUid($pageUid)
            ->setArguments($additionalParams);
        if ($rssFormat) {
            $rssTypeNum = RequestUtility::getTypoScriptTypeNum($request, 'blog_rss_author');
            $uriBuilder
                ->setTargetPageType($rssTypeNum);
        }

        return $uriBuilder;
    }

    protected function buildAnchorTag(string $uri, Author $author): string
    {
        if ($uri !== '') {
            $linkText = TypeUtility::toString($this->renderChildren(), TypeUtility::toString($author->getName()));
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($linkText);
            return $this->tag->render();
        }

        return TypeUtility::toString($this->renderChildren(), TypeUtility::toString($author->getName()));
    }

    /**
     * @return RequestInterface&ServerRequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        $renderingContext = $this->renderingContext;
        $request = null;
        if ($renderingContext !== null && $renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        }

        if ($request === null || !$request instanceof RequestInterface) {
            throw new \RuntimeException(
                'ViewHelper blogvh:link.author can be used only in extbase context and needs a request implementing extbase RequestInterface.',
                1729082934
            );
        }

        return $request;
    }
}
