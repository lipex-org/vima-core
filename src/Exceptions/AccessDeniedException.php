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

namespace Vima\Core\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a user is not authorized to perform an action.
 */
class AccessDeniedException extends RuntimeException
{
    public function __construct(
        public readonly string $permission,
        public readonly mixed $user = null,
        public readonly ?string $userId = null,
        string $message = ""
    ) {
        if ($message === "") {
            $userPart = $userId !== null ? "user [{$userId}]" : "user";
            $message = "Access denied for {$userPart} on permission '{$permission}'";
        }
        parent::__construct($message);
    }

    public static function forPermission(string $permission, mixed $user = null, mixed $userResolver = null): self
    {
        $userId = null;
        if ($user !== null) {
            if ($userResolver !== null && method_exists($userResolver, 'resolveId')) {
                try {
                    $userId = (string) $userResolver->resolveId($user);
                } catch (\Throwable $e) {
                }
            } elseif (method_exists($user, 'vimaGetId')) {
                $userId = (string) $user->vimaGetId();
            } elseif (method_exists($user, 'getId')) {
                $userId = (string) $user->getId();
            } elseif (isset($user->id)) {
                $userId = (string) $user->id;
            }
        }

        return new self($permission, $user, $userId);
    }
}
