<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Utility;

final class TcaUtility
{
    /**
     * @return array<int|string, mixed>
     */
    public static function getTableTca(string $table): array
    {
        $tca = $GLOBALS['TCA'] ?? null;
        if (!is_array($tca)) {
            return [];
        }

        $tableTca = $tca[$table] ?? null;

        return is_array($tableTca) ? $tableTca : [];
    }

    /**
     * @param array<int|string, mixed> $tableTca
     */
    public static function setTableTca(string $table, array $tableTca): void
    {
        if (!is_array($GLOBALS['TCA'] ?? null)) {
            $GLOBALS['TCA'] = [];
        }

        $GLOBALS['TCA'][$table] = $tableTca;
    }

    /**
     * @param array<string|int, mixed> $array
     * @param list<string|int> $path
     */
    public static function getNestedValue(array $array, array $path): mixed
    {
        $value = $array;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $array
     * @param list<string|int> $path
     * @return array<string|int, mixed>
     */
    public static function getNestedArray(array $array, array $path): array
    {
        $value = self::getNestedValue($array, $path);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string|int, mixed> $array
     * @param list<string|int> $path
     */
    public static function getNestedString(array $array, array $path, string $default = ''): string
    {
        return TypeUtility::toString(self::getNestedValue($array, $path), $default);
    }

    /**
     * @param array<string|int, mixed> $array
     * @param list<string|int> $path
     */
    public static function setNestedValue(array &$array, array $path, mixed $value): void
    {
        if ($path === []) {
            return;
        }

        $current = &$array;
        $lastKey = array_pop($path);

        foreach ($path as $key) {
            $child = $current[$key] ?? null;
            if (!is_array($child)) {
                $child = [];
            }
            $current[$key] = $child;
            $current = &$current[$key];
        }

        $current[$lastKey] = $value;
    }

    /**
     * @param array<string|int, mixed> $array
     * @param list<string|int> $path
     */
    public static function appendNestedValue(array &$array, array $path, mixed $value): void
    {
        $items = self::getNestedArray($array, $path);
        $items[] = $value;
        self::setNestedValue($array, $path, $items);
    }
}
