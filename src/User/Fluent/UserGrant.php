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

use Vima\Core\Events\DomainEvent;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Contracts\UserRoleRepositoryInterface;
use Vima\Core\User\Contracts\UserPermissionRepositoryInterface;
use Vima\Core\User\Entities\UserRole;
use Vima\Core\User\Entities\UserPermission;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Cache\Services\CacheVersionManager;
use function Vima\Core\resolve;

/**
 * Class UserGrant
 * 
 * Fluent API for granting roles and permissions to a user.
 */
class UserGrant
{
    private ?CacheVersionManager $versionManager = null;

    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserRoleRepositoryInterface $userRoles,
        private UserPermissionRepositoryInterface $userPermissions,
        private EventDispatcherInterface $dispatcher,
        ?CacheVersionManager $versionManager = null
    ) {
        $this->versionManager = $versionManager ?? (function_exists('Vima\Core\resolve') ? resolve(CacheVersionManager::class) : null);
    }

    public function role(string|Role|array $role, array $context = []): void
    {
        if (is_array($role)) {
            foreach ($role as $key => $value) {
                if (is_string($key)) {
                    $this->role($key, is_array($value) ? $value : $context);
                } else {
                    $this->role($value, $context);
                }
            }
            return;
        }

        $roleEntity = $this->roleService->find($role);
        if (!$roleEntity) {
            if (is_string($role) && str_contains($role, ':')) {
                [$ns, $name] = \Vima\Core\Support\Utils\Utils::resolveNamespace($role);
                $baseRole = $this->roleService->find($name);
                if ($baseRole) {
                    $newRole = clone $baseRole;
                    $newRole->id = null;
                    $newRole->namespace = $ns;
                    $newRole->name = $name;
                    $roleEntity = $this->roleService->save($newRole);
                    
                    // Add mapped permissions from base role
                    $baseRes = \Vima\Core\Vima::role($baseRole);
                    $newRes = \Vima\Core\Vima::role($roleEntity);
                    foreach ($baseRes->permissions()->all() as $bp) {
                        $newRes->permissions()->add($bp);
                    }
                }
            }
        }
        if (!$roleEntity) {
            return;
        }

        $this->userRoles->assign(new UserRole(
            userId: $this->userId,
            roleId: $roleEntity->id,
            context: $context
        ));

        if ($this->versionManager !== null) {
            $this->versionManager->bumpUserEpoch($this->userId);
        } else {
            CacheVersionManager::clearL1User($this->userId);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.user.role_granted', [
            'userId' => $this->userId,
            'role' => $roleEntity,
            'context' => $context
        ]));
    }

    public function permission(string|Permission|array $permission, array $constraints = []): void
    {
        if (is_array($permission)) {
            foreach ($permission as $key => $value) {
                if (is_string($key)) {
                    $this->permission($key, is_array($value) ? $value : $constraints);
                } else {
                    $this->permission($value, $constraints);
                }
            }
            return;
        }

        $permissionEntity = $this->permissionService->find($permission);
        if (!$permissionEntity) {
            return;
        }

        $this->userPermissions->add(new UserPermission(
            userId: $this->userId,
            permissionId: $permissionEntity->id,
            constraints: $constraints
        ));

        if ($this->versionManager !== null) {
            $this->versionManager->bumpUserEpoch($this->userId);
        } else {
            CacheVersionManager::clearL1User($this->userId);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.user.permission_granted', [
            'userId' => $this->userId,
            'permission' => $permissionEntity,
            'constraints' => $constraints
        ]));
    }
}
