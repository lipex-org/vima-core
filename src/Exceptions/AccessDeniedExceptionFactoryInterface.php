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

/**
 * Interface for factories generating access denied exceptions.
 */
interface AccessDeniedExceptionFactoryInterface
{
    /**
     * Create a custom AccessDeniedException instance.
     */
    public function create(string $permission, mixed $user = null, mixed $userResolver = null): \Throwable&AccessDeniedExceptionInterface;
}
