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

namespace Vima\Core\User\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\User\Exceptions\UserResolutionException;

/**
 * Class UserResolutionService
 * 
 * Responsible for extracting a unique identifier from user objects.
 */
final class UserResolutionService
{
    public function __construct(
        private readonly ?VimaConfig $config = null,
    ) {
    }

    public function resolveId(object|array $user): int|string
    {
        $resolvedId = null;

        if (is_object($user) && method_exists($user, 'vimaGetId')) {
            $resolvedId = $user->vimaGetId();
        }

        if ($resolvedId === null && is_object($user) && isset($user->id)) {
            $resolvedId = $user->id;
        }

        if ($resolvedId === null && is_object($user) && !empty($this->config?->userMethods?->id)) {
            $mappedMethod = $this->config->userMethods->id;
            if (method_exists($user, $mappedMethod)) {
                $resolvedId = $user->{$mappedMethod}();
            }
        }

        if ($resolvedId === null && $this->config?->userResolver !== null) {
            $resolvedId = ($this->config->userResolver)($user);
        }

        if ($resolvedId === null) {
            throw new UserResolutionException(
                "Could not resolve user ID. Configure Vima::userResolver or ensure a valid user method is defined."
            );
        }

        return is_scalar($resolvedId) ? (string) $resolvedId : $resolvedId;
    }
}
