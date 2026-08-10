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

use Closure;
use RuntimeException;

/**
 * Exception thrown when a user is not authorized to perform an action.
 */
class AccessDeniedException extends RuntimeException implements AccessDeniedExceptionInterface
{
    private static ?Closure $factory = null;

    /**
     * Set a custom factory callback to instantiate the exception.
     * The callback should receive (string $permission, mixed $user, mixed $userResolver)
     * and return an instance of Throwable implementing AccessDeniedExceptionInterface.
     */
    public static function useFactory(?Closure $factory): void
    {
        self::$factory = $factory;
    }

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

    public function getPermission(): string
    {
        return $this->permission;
    }

    public function getUser(): mixed
    {
        return $this->user;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public static function forPermission(string $permission, mixed $user = null, mixed $userResolver = null): \Throwable&AccessDeniedExceptionInterface
    {
        if (self::$factory !== null) {
            return (self::$factory)($permission, $user, $userResolver);
        }

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
