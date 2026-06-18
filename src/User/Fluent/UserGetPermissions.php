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

namespace Vima\Core\User\Fluent;

use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;

class UserGetPermissions
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserPermissionRepositoryInterface $userPermissions,
        private UserGet $userGet
    ) {
    }

    public function all(array $context = []): array
    {
        // ... (This evaluates both direct permissions and roles' permissions)
        return [];
    }

    public function direct(): array
    {
        $userPerms = $this->userPermissions->findByUserId($this->userId);
        $perms = [];
        foreach ($userPerms as $up) {
            $p = clone $this->permissionService->find($up->permissionId);
            if ($p) {
                $p->constraints = $up->constraints ?? [];
                $perms[] = $p;
            }
        }
        return array_filter($perms);
    }
}
