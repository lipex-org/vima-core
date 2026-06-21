<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/lipex-org/vima-core>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vima\Core\Support\Utils;

/**
 * Class Utils
 * 
 * Shared static utility helpers for the Vima core.
 */
final class Utils
{
    /**
     * Splits a permission or role string into namespace and name.
     * e.g. "tenant:posts.edit" -> ["tenant", "posts.edit"]
     *
     * @param string $permission
     * @return array{0: string|null, 1: string}
     */
    public static function resolveNamespace(string $permission): array
    {
        if (str_contains($permission, ':')) {
            $parts = explode(':', $permission, 2);
            return [$parts[0], $parts[1]];
        }

        return [null, $permission];
    }

    /**
     * Sorts an array recursively by keys.
     *
     * @param array $array
     * @return array
     */
    public static function sortRecursive(array $array): array
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursive($value);
            }
        }
        return $array;
    }
}
