<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use T3G\AgencyPack\Blog\Constants;

final class BlogFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return list<ExpressionFunction>
     */
    public function getFunctions(): array
    {
        return [
            $this->createDoktypeFunction('isBlogPage', Constants::DOKTYPE_BLOG_PAGE),
            $this->createDoktypeFunction('isBlogPost', Constants::DOKTYPE_BLOG_POST),
        ];
    }

    private function createDoktypeFunction(string $name, int $doktype): ExpressionFunction
    {
        return new ExpressionFunction(
            $name,
            static fn (): null => null,
            static function (array $arguments) use ($doktype): bool {
                $page = $arguments['page'] ?? null;

                return is_array($page) && ($page['doktype'] ?? null) === $doktype;
            },
        );
    }
}
