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
use Vima\Core\Role\Services\RoleService;
use Vima\Core\Policy\Services\PolicyRegistry;

/**
 * Class DeploymentService
 * 
 * Orchestrates optimization and maintenance tasks for production environments.
 */
class DeploymentService
{
    public function __construct(
        private RoleService $roleService,
        private PolicyRegistry $policyRegistry,
        private CacheInterface $cache
    ) {
    }

    /**
     * Pre-warm all caches to eliminate runtime reflection and recursion.
     *
     * @return array Summary of optimized items.
     */
    public function optimize(): array
    {
        $this->clear();

        $stats = [
            'roles' => 0,
            'policies' => 0
        ];

        // 1. Warm Role Inheritance Caches
        $roles = $this->roleService->all();
        foreach ($roles as $role) {
            $this->roleService->role($role)->permissions()->all();
            $stats['roles']++;
        }

        // 2. Warm Policy Attribute Maps
        $policies = $this->policyRegistry->getRegisteredClasses();
        $reflectionMethod = new \ReflectionMethod($this->policyRegistry, 'resolveMethodViaAttributes');

        foreach ($policies as $resource => $policyClass) {
            $reflectionMethod->invoke($this->policyRegistry, $policyClass, '__warmup__', null);
            $stats['policies']++;
        }

        return $stats;
    }

    /**
     * Wipe all Vima caches.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }
}
