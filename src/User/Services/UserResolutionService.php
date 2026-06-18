<?php
/**
 * This file is part of Vima PHP.
 *
 * (c) Vima PHP <https://github.com/lipex-org>
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

    /**
     * @param object|array $user
     * @return int|string
     * @throws UserResolutionException
     */
    public function resolveId(object|array $user): int|string
    {
        if ($this->config?->userResolver !== null) {
            return ($this->config->userResolver)($user);
        }

        if (is_array($user)) {
            throw new UserResolutionException("Use the Vima::userResolver property to provide a resolver for the user");
        }

        if (method_exists($user, 'vimaGetId')) {
            return $user->vimaGetId();
        }

        $mappedMethod = $this->config?->userMethods?->id ?? null;
        if ($mappedMethod && method_exists($user, $mappedMethod)) {
            return $user->{$mappedMethod}();
        }

        throw new UserResolutionException('Could not resolve user ID.');
    }
}
