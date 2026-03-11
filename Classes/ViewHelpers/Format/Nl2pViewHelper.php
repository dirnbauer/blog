<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class Nl2pViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'string to format');
    }

    public function render(): string
    {
        $content = htmlspecialchars((string)$this->renderChildren(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $data = explode('<br>', nl2br($content, false));
        $data = array_filter($data, static function ($value) {
            return trim($value) !== '';
        });
        return '<p>' . implode('</p><p>', $data) . '</p>';
    }
}
