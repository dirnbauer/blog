<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Utility;

/**
 * Replaces NEW* placeholder keys/values in DataHandler datamap arrays with real UIDs.
 */
final class DataHandlerUidReplacer
{
    /**
     * @param array<string|int, mixed> $setup
     * @param array<string, int|string> $substitutions
     *
     * @return array<string|int, mixed>
     */
    public function replace(array $setup, array $substitutions): array
    {
        $result = [];
        foreach ($setup as $key => $value) {
            $resolvedKey = $this->replaceTokens((string)$key, $substitutions);
            if (is_array($value)) {
                $result[$resolvedKey] = $this->replace($value, $substitutions);
                continue;
            }
            $result[$resolvedKey] = is_string($value)
                ? $this->replaceTokens($value, $substitutions)
                : $value;
        }

        return $result;
    }

    /**
     * @param array<string, int|string> $substitutions
     */
    private function replaceTokens(string $subject, array $substitutions): string
    {
        $result = $subject;
        foreach ($substitutions as $placeholder => $uid) {
            $result = str_replace((string)$placeholder, (string)$uid, $result);
        }

        return $result;
    }
}
