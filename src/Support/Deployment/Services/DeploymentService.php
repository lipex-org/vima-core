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

namespace Vima\Core\Support\Deployment\Services;

use Vima\Core\Cache\Contracts\CacheInterface;
use Vima\Core\Cache\Services\CacheVersionManager;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Permission\Services\PermissionService;
use Vima\Core\User\Services\UserService;
use Vima\Core\Policy\Services\PolicyRegistry;
use function Vima\Core\resolve;

/**
 * Class DeploymentService
 * 
 * Orchestrates optimization and maintenance tasks for production environments.
 */
class DeploymentService
{
    private ?CacheVersionManager $versionManager = null;
    private ?UserService $userService = null;
    private ?PermissionService $permissionService = null;

    public function __construct(
        private RoleService $roleService,
        private PolicyRegistry $policyRegistry,
        private CacheInterface $cache,
        ?CacheVersionManager $versionManager = null,
        ?UserService $userService = null,
        ?PermissionService $permissionService = null
    ) {
        $this->versionManager = $versionManager ?? (function_exists('Vima\Core\resolve') ? resolve(CacheVersionManager::class) : null);
        $this->userService = $userService ?? (function_exists('Vima\Core\resolve') ? resolve(UserService::class) : null);
        $this->permissionService = $permissionService ?? (function_exists('Vima\Core\resolve') ? resolve(PermissionService::class) : null);
    }

    /**
     * Pre-warm all caches to eliminate runtime reflection and recursion.
     *
     * @param array<int|string|object> $users Optional list of active users to pre-compile matrices for.
     * @param array<string> $matrixFilter Optional list of permission names to pre-warm in matrix.
     * @return array Summary of optimized items.
     */
    public function optimize(array $users = [], array $matrixFilter = []): array
    {
        $this->clear();

        $stats = [
            'roles' => 0,
            'policies' => 0,
            'permissions' => 0,
            'users' => 0,
        ];

        // 1. Warm All System Permissions Index
        if ($this->permissionService !== null) {
            $allPerms = $this->permissionService->all();
            $stats['permissions'] = count($allPerms);
        }

        // 2. Warm Role Inheritance & Permission Trees
        $roles = $this->roleService->all();
        foreach ($roles as $role) {
            $this->roleService->role($role)->permissions()->all();
            $stats['roles']++;
        }

        // 3. Warm Policy Attribute Maps
        $policyStats = $this->policyRegistry->warmCache(force: true);
        $stats['policies'] = count($policyStats);

        // 4. Warm Specific User Matrices (if provided)
        if ($this->userService !== null && !empty($users)) {
            foreach ($users as $user) {
                $userObj = is_object($user) ? $user : (object)['id' => $user];
                $userResource = $this->userService->user($userObj);
                // Pre-compile role trees & permission map
                $userResource->get()->compiled();
                // Pre-compile boolean matrix for Inertia / frontend
                $userResource->matrix($matrixFilter);
                $stats['users']++;
            }
        }

        return $stats;
    }

    /**
     * Wipe all Vima caches safely without flushing non-Vima application cache.
     */
    public function clear(): void
    {
        CacheVersionManager::clearL1();

        if ($this->versionManager !== null) {
            $this->versionManager->bumpGlobalEpoch();
            return;
        }

        $this->cache->clear();
    }
}
