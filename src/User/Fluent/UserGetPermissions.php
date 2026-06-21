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
        $compiled = $this->userGet->compiled($context);
        $perms = [];
        foreach ($compiled as $fullName => $constraintsArray) {
            $existing = $this->permissionService->find($fullName);
            if ($existing) {
                foreach ($constraintsArray as $constraints) {
                    $p = clone $existing;
                    $p->constraints = $constraints;
                    $perms[] = $p;
                }
            }
        }
        return $perms;
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
