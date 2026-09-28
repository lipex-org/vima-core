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
use DateTimeInterface;
use Vima\Core\Events\DomainEvent;
use Vima\Core\Cache\Services\CacheVersionManager;
use function Vima\Core\resolve;

/**
 * Class UserDeny
 * 
 * Fluent API for explicitly denying roles and permissions to a user.
 */
class UserDeny
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

    public function role(string|Role|array $role, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        if (is_array($role)) {
            foreach ($role as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value)) {
                        $this->role(
                            $key,
                            $value['reason'] ?? $value[0] ?? $reason,
                            $value['expiresAt'] ?? $value['expires'] ?? $value[1] ?? $expiresAt
                        );
                    } else {
                        $this->role($key, $value, $expiresAt);
                    }
                } else {
                    $this->role($value, $reason, $expiresAt);
                }
            }
            return;
        }

        $roleEntity = $this->roleService->find($role);
        if (!$roleEntity) {
            return;
        }

        $this->userRoleDenies->add(
            $this->userId,
            $roleEntity->id,
            $reason,
            $expiresAt
        );

        if ($this->versionManager !== null) {
            $this->versionManager->bumpUserEpoch($this->userId);
        } else {
            CacheVersionManager::clearL1User($this->userId);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.user.role_denied', [
            'userId' => $this->userId,
            'role' => $roleEntity,
            'reason' => $reason,
            'expiresAt' => $expiresAt
        ]));
    }

    public function permission(string|Permission|array $permission, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        if (is_array($permission)) {
            foreach ($permission as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value)) {
                        $this->permission(
                            $key,
                            $value['reason'] ?? $value[0] ?? $reason,
                            $value['expiresAt'] ?? $value['expires'] ?? $value[1] ?? $expiresAt
                        );
                    } else {
                        $this->permission($key, $value, $expiresAt);
                    }
                } else {
                    $this->permission($value, $reason, $expiresAt);
                }
            }
            return;
        }

        $permissionEntity = $this->permissionService->find($permission);
        if (!$permissionEntity) {
            return;
        }

        $this->userDenies->add(
            $this->userId,
            $permissionEntity->id,
            $reason,
            $expiresAt
        );

        if ($this->versionManager !== null) {
            $this->versionManager->bumpUserEpoch($this->userId);
        } else {
            CacheVersionManager::clearL1User($this->userId);
        }

        $this->dispatcher->dispatch(new DomainEvent('vima.user.permission_denied', [
            'userId' => $this->userId,
            'permission' => $permissionEntity,
            'reason' => $reason,
            'expiresAt' => $expiresAt
        ]));
    }
}
