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
use T3G\AgencyPack\Blog\Domain\Model\Category;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class CategoryViewHelper extends AbstractTagBasedViewHelper
{
    public function __construct()
    {
        $this->tagName = 'a';
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('category', Category::class, 'The category to link to', true);
        $this->registerArgument('rss', 'bool', 'Link to rss version', false, false);
    }

    public function render(): string
    {
        $request = $this->getRequest();

        $rssFormat = (bool)$this->arguments['rss'];
        /** @var Category $category */
        $category = $this->arguments['category'];
        $pageUid = RequestUtility::getSiteSettingInt($request, 'plugin.tx_blog.settings.categoryUid');
        $arguments = [
            'category' => $category->getUid(),
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset()
            ->setRequest($request)
            ->setTargetPageUid($pageUid);
        if ($rssFormat) {
            $rssTypeNum = RequestUtility::getTypoScriptTypeNum($request, 'blog_rss_category');
            $uriBuilder->setTargetPageType($rssTypeNum);
        }
        $uri = $uriBuilder->uriFor('listPostsByCategory', $arguments, 'Post', 'Blog', 'Category');

        if ($uri !== '') {
            $linkText = TypeUtility::toString($this->renderChildren(), $category->getTitle());
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($linkText);
            $result = $this->tag->render();
        } else {
            $result = TypeUtility::toString($this->renderChildren(), $category->getTitle());
        }

        return TypeUtility::toString($result);
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
                'ViewHelper blogvh:link.category can be used only in extbase context and needs a request implementing extbase RequestInterface.',
                1729082935
            );
        }

        return $request;
    }
}
