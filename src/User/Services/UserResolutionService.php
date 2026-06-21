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
        if ($this->config?->userResolver !== null) {
            $resolvedId = ($this->config->userResolver)($user);
        } elseif (is_array($user)) {
            throw new UserResolutionException("Use the Vima::userResolver property to provide a resolver for the user");
        } elseif (method_exists($user, 'vimaGetId')) {
            $resolvedId = $user->vimaGetId();
        } else {
            $mappedMethod = $this->config?->userMethods?->id ?? null;
            if ($mappedMethod && method_exists($user, $mappedMethod)) {
                $resolvedId = $user->{$mappedMethod}();
            }
        }

        if ($resolvedId === null) {
            throw new UserResolutionException('Could not resolve user ID.');
        }

        return is_scalar($resolvedId) ? (string) $resolvedId : $resolvedId;
    }
}
