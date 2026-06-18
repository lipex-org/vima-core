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

namespace Vima\Core\Config\Services;

use Vima\Core\Config\VimaConfig;
use Vima\Core\Permission\Entities\Permission;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Support\Utils\Utils;
use InvalidArgumentException;

/**
 * Class ConfigResolver
 * 
 * Aggregates and expands wildcard permissions, ensuring no data loss for Entity definitions.
 */
class ConfigResolver
{
    private array $resolvedPermissions = [];
    private array $resolvedRoles = [];
    
    public function __construct(private VimaConfig $config)
    {
        $this->resolve();
    }

    private function resolve(): void
    {
        // 1. Index explicit Permission objects
        foreach ($this->config->setup->permissions as $perm) {
            $this->resolvedPermissions[$perm->getFullName()] = $perm;
        }

        // 2. Iterate Roles and resolve string permissions into objects
        foreach ($this->config->setup->roles as $role) {
            $this->resolvedRoles[$role->getFullName()] = clone $role;

            $hydratedPerms = [];
            foreach ($role->permissions as $permDef) {
                if ($permDef instanceof Permission) {
                    $this->resolvedPermissions[$permDef->getFullName()] = $permDef;
                    $hydratedPerms[] = $permDef;
                } elseif (is_string($permDef)) {
                    // Extract namespace logic
                    [$ns, $name] = Utils::resolveNamespace($permDef);
                    $fullName = $ns ? "{$ns}:{$name}" : $name;

                    if (!isset($this->resolvedPermissions[$fullName])) {
                        $this->resolvedPermissions[$fullName] = Permission::define($name, namespace: $ns);
                    }
                    $hydratedPerms[] = $this->resolvedPermissions[$fullName];
                }
            }
            $this->resolvedRoles[$role->getFullName()]->permissions = $hydratedPerms;

            // Optional: Map children to parents for simplicity later
            foreach ($role->children as $childDef) {
                [$ns, $name] = Utils::resolveNamespace(is_string($childDef) ? $childDef : $childDef->name);
                $fullChild = $ns ? "{$ns}:{$name}" : $name;
                
                if (!isset($this->resolvedRoles[$fullChild])) {
                    // Create stub child if it doesn't exist yet
                    $this->resolvedRoles[$fullChild] = Role::define($name, namespace: $ns);
                }
                
                // Add this role as a parent to the child
                if (!in_array($role->getFullName(), $this->resolvedRoles[$fullChild]->parents)) {
                    $this->resolvedRoles[$fullChild]->parents[] = $role->getFullName();
                }
            }
        }
        
        // Secondary pass: if a role was redefined AFTER it was created as a stub child, 
        // its parents array might have been overwritten by the original definition.
        // We must merge the parents back in.
        foreach ($this->config->setup->roles as $role) {
            foreach ($role->children as $childDef) {
                [$ns, $name] = Utils::resolveNamespace(is_string($childDef) ? $childDef : $childDef->name);
                $fullChild = $ns ? "{$ns}:{$name}" : $name;
                
                if (!in_array($role->getFullName(), $this->resolvedRoles[$fullChild]->parents)) {
                    $this->resolvedRoles[$fullChild]->parents[] = $role->getFullName();
                }
            }
        }
        
        // Final pass to ensure string-based parents definitions ensure the parent exists
        foreach ($this->resolvedRoles as $role) {
            foreach ($role->parents as $parentDef) {
                [$ns, $name] = Utils::resolveNamespace(is_string($parentDef) ? $parentDef : $parentDef->name);
                $fullParent = $ns ? "{$ns}:{$name}" : $name;
                if (!isset($this->resolvedRoles[$fullParent])) {
                    $this->resolvedRoles[$fullParent] = Role::define($name, namespace: $ns);
                }
            }
        }
    }

    /**
     * @return Permission[]
     */
    public function getPermissions(): array
    {
        return array_values($this->resolvedPermissions);
    }

    /**
     * @return Role[]
     */
    public function getRoles(): array
    {
        return array_values($this->resolvedRoles);
    }
}
