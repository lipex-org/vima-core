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

namespace Vima\Core\Permission\Fluent;

use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;

/**
 * Class PermissionResource
 * 
 * Fluent API root for permission-specific operations.
 */
class PermissionResource
{
    public function __construct(
        private Permission $permission,
        private PermissionService $permissionService,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function getOriginalPermission(): Permission
    {
        return $this->permission;
    }

    public function delete(): void
    {
        $this->permissionService->delete($this->permission);
    }
}
