<?php

declare(strict_types=1);

namespace Vima\Core\Policy\DTOs;

use Vima\Core\Support\Utils\Utils;
use function Vima\Core\resolve;
class AccessContext
{
    public function __construct(
        public object $user,
        public string $permission,
        private \Vima\Core\AuthorizationService $manager,
        public ?string $namespace = null,
        public array $additionalContext = [],
    ) {
    }

    /**
     * Helpful wrapper so the policy doesn't have to resolve the manager manually
     */
    public function hasRole(string|array $roleName, bool $useAny = true): bool
    {
        $userService = resolve(\Vima\Core\User\Services\UserService::class);
        $roleService = resolve(\Vima\Core\Role\Services\RoleService::class);
        $roles = $userService->user($this->user)->get()->roles(true);

        $tenantId = $this->user->tenant_id ?? null;
        if ($tenantId !== null) {
            $expectedNamespace = 'tenant_' . $tenantId;
            $roles = array_filter($roles, function ($role) use ($expectedNamespace) {
                return $role->namespace === null || $role->namespace === $expectedNamespace;
            });
        }

        $hasRoleRecursive = function ($role, $target) use (&$hasRoleRecursive, $roleService) {
            $fullName = $role->getFullName();
            $simpleName = $role->name;
            if ($fullName === $target || $simpleName === $target) {
                return true;
            }

            // A user has $target role if any of their active roles inherits/includes $target.
            $roleEntity = $roleService->find($role);
            if ($roleEntity) {
                // Check parent roles
                $parents = $roleEntity->parents ?? [];
                foreach ($parents as $parent) {
                    $parentEntity = $roleService->find($parent);
                    if ($parentEntity && $hasRoleRecursive($parentEntity, $target)) {
                        return true;
                    }
                }

                // Let's also check database parent relationships.
                $roleParentsRepo = resolve(\Vima\Core\Role\Contracts\RoleParentRepositoryInterface::class);
                $parentRelations = $roleParentsRepo->getParents($roleEntity);
                foreach ($parentRelations as $rel) {
                    $parentEntity = $roleService->find($rel->parentId);
                    if ($parentEntity && $parentEntity->getFullName() !== $roleEntity->getFullName() && $hasRoleRecursive($parentEntity, $target)) {
                        return true;
                    }
                }
            }
            return false;
        };

        $targets = is_array($roleName) ? $roleName : [$roleName];

        if ($useAny) {
            foreach ($roles as $role) {
                foreach ($targets as $target) {
                    if ($hasRoleRecursive($role, $target)) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            foreach ($targets as $target) {
                $found = false;
                foreach ($roles as $role) {
                    if ($hasRoleRecursive($role, $target)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * Checks if user has the given role
     * @param string $roleName
     * @return bool
     */
    public function is(string $roleName): bool
    {
        return $this->hasRole($roleName, useAny: true);
    }
    /**
     * Checks if user has any of the provided roles
     * @param array $roleNames
     * @return bool
     */
    public function isAny(array $roleNames): bool
    {
        return $this->hasRole($roleNames, useAny: true);
    }

    /**
     * Checks if user has all the given roles
     * @param array $roleNames
     * @return bool
     */
    public function isAll(array $roleNames): bool
    {
        return $this->hasRole($roleNames, useAny: false);
    }

    /**
     * Checks if user has the designated super admin role. if configured. Super admins bypass all checks.
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->manager->isSuperAdmin($this->user);
    }

    /**
     * A helper to check if the current user owns the resource.
     * Assumes the resource has a user_id or similar.
     * @param mixed $resource
     * @param string $ownerKey The key to check for ownership, defaults to 'user_id'
     * @return bool
     */
    public function owns(mixed $resource, string $ownerKey = 'user_id'): bool
    {
        $userId = $this->resolveId();
        if (is_array($resource)) {
            return ($resource[$ownerKey] ?? null) === $userId;
        }

        if (is_object($resource)) {
            return ($resource->{$ownerKey} ?? null) === $userId;
        }

        return false;
    }

    /**
     * Performs an RBAC check on the user with the given permission
     * @param string $permission Permission name, can be namespaced like 'blog:edit'
     * @return bool
     */
    public function can(string $permission): bool
    {
        [$namespace, $name] = Utils::splitPermission($permission);
        return $this->manager->can($this->user, $name, $namespace);
    }

    public function resolveId(): int|string|null
    {
        /** @var \Vima\Core\User\Services\UserResolutionService $userResolver */
        $userResolver = resolve(\Vima\Core\User\Services\UserResolutionService::class);
        return $userResolver->resolveId($this->user);
    }
}