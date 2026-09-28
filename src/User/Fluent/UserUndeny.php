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
use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
use Vima\Core\Support\Utils\Utils;

use DateTimeInterface;
use Vima\Core\Events\DomainEvent;
use Vima\Core\Cache\Services\CacheVersionManager;
use function Vima\Core\resolve;

class UserUndeny
{
    private ?CacheVersionManager $versionManager = null;

    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private PermissionService $permissionService,
        private UserDenyRepositoryInterface $userDenies,
        private UserRoleDenyRepositoryInterface $userRoleDenies,
        private EventDispatcherInterface $dispatcher,
        ?CacheVersionManager $versionManager = null
    ) {
        $this->versionManager = $versionManager ?? (function_exists('Vima\Core\resolve') ? resolve(CacheVersionManager::class) : null);
    }

    public function role(string|Role|array $role): void
    {
        if (is_array($role)) {
            foreach ($role as $r) {
                $this->role($r);
            }
            return;
        }

        $roleEntity = $this->roleService->find($role);
        if (!$roleEntity) {
            return;
        }

        $this->userRoleDenies->remove($this->userId, $roleEntity->id);

        if ($this->versionManager !== null) {
            $this->versionManager->bumpUserEpoch($this->userId);
        } else {
            CacheVersionManager::clearL1User($this->userId);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.user.role_undenied', [
            'userId' => $this->userId,
            'role' => $roleEntity
        ]));
    }

    public function permission(string|Permission|array $permission): void
    {
        if (is_array($permission)) {
            foreach ($permission as $p) {
                $this->permission($p);
            }
            return;
        }

        if (is_string($permission)) {
            [$namespace, $name] = Utils::resolveNamespace($permission);
            if ($name === '*') {
                $pid = ($namespace ? $namespace . ':' : '') . '*';
                $this->userDenies->remove($this->userId, $pid);

                if ($this->versionManager !== null) {
                    $this->versionManager->bumpUserEpoch($this->userId);
                } else {
                    CacheVersionManager::clearL1User($this->userId);
                }

                $this->dispatcher->dispatch(new DomainEvent('vima.user.permission_undenied', [
                    'userId' => $this->userId,
                    'permission' => $pid
                ]));
                return;
            }
        }

        $permissionEntity = $this->permissionService->find($permission);
        if ($permissionEntity) {
            $this->userDenies->remove($this->userId, $permissionEntity->id);

            if ($this->versionManager !== null) {
                $this->versionManager->bumpUserEpoch($this->userId);
            } else {
                CacheVersionManager::clearL1User($this->userId);
            }

            $this->dispatcher->dispatch(new DomainEvent('vima.user.permission_undenied', [
                'userId' => $this->userId,
                'permission' => $permissionEntity
            ]));
        }
    }
}
