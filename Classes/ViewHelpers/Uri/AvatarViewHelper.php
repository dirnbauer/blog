<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ViewHelpers\Uri;

use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Utility\TypeUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class AvatarViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('author', Author::class, 'Author', true);
        $this->registerArgument('size', 'int', 'The size of the avatar, ranging from 1 to 512', false, 64);
    }

    public function render(): string
    {
        /** @var Author $author */
        $author = $this->arguments['author'];
        $size = TypeUtility::toInt($this->arguments['size'], 64);

        return $author->getAvatar($size);
    }
}
