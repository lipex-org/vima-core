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

namespace Vima\Core;

use Vima\Core\Events\Access\AccessDenied;
use Vima\Core\Events\Access\AuthorizationChecked;
use Vima\Core\Role\Services\RoleService;
use Vima\Core\User\Services\UserService;
use Vima\Core\Config\VimaConfig;
use Vima\Core\Support\Utils\Utils;
use Vima\Core\Policy\Services\PolicyRegistry;
use Vima\Core\Events\Contracts\EventDispatcherInterface;
// Note: Additional classes like AccessDeniedException would need to be migrated/created.

/**
 * Class AuthorizationService
 * 
 * Core evaluation engine for access control.
 */
class AuthorizationService
{
    public function __construct(
        private UserService $userService,
        private RoleService $roleService,
        private PolicyRegistry $policyRegistry,
        private VimaConfig $config,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function isPermitted(object $user, string $permission, array $context = []): bool
    {
        $userRes = $this->userService->user($user);

        if ($userRes->is()->superAdmin() && $this->config->superAdminBypass) {
            return true;
        }

        if ($userRes->is()->denied()->permission($permission)) {
            return false;
        }

        [$namespace, $permName] = Utils::resolveNamespace($permission);
        $fullName = $namespace ? "{$namespace}:{$permName}" : $permName;

        $checkConstraints = function (?array $constraints) use ($context) {
            if (!$constraints)
                return true;
            foreach ($constraints as $key => $val) {
                if (!isset($context[$key]) || $context[$key] != $val)
                    return false;
            }
            return true;
        };

        // Note: `compiled` method in UserGet needs to evaluate all granted permissions 
        // across roles and direct assignments, matching against contexts.
        $compiled = $userRes->get()->compiled($context);

        if (empty($context)) {
            if (isset($compiled[$fullName]) && empty($compiled[$fullName]))
                return true;
            foreach ($compiled as $comp => $constraints) {
                if (empty($constraints) && str_ends_with($comp, '*')) {
                    if (str_starts_with($fullName, rtrim($comp, '*')))
                        return true;
                }
            }
        } else {
            if (isset($compiled[$fullName]) && $checkConstraints($compiled[$fullName]))
                return true;
            foreach ($compiled as $comp => $constraints) {
                if (str_ends_with($comp, '*')) {
                    if (str_starts_with($fullName, rtrim($comp, '*')) && $checkConstraints($constraints))
                        return true;
                }
            }
        }

        // Check fallback through user's active roles and direct permissions
        $roles = array_filter($userRes->get()->roles(true), fn($r) => !$userRes->is()->denied()->role($r));
        $tenantId = $user->tenant_id ?? null;
        if ($tenantId !== null) {
            $expectedNamespace = 'tenant_' . $tenantId;
            $roles = array_filter($roles, function ($role) use ($expectedNamespace) {
                return $role->namespace === null || $role->namespace === $expectedNamespace;
            });
        }
        if (!empty($context)) {
            $roles = array_filter($roles, function ($r) use ($context) {
                foreach ($context as $k => $v) {
                    if (!isset($r->context[$k]) || $r->context[$k] != $v)
                        return false;
                }
                return true;
            });
        }

        foreach ($roles as $role) {
            $rolePerms = $this->roleService->role($role)->permissions()->all();
            foreach ($rolePerms as $perm) {
                $match = false;
                if ($perm->name === $permName) {
                    $match = true;
                } elseif (str_ends_with($perm->name, '*') && str_starts_with($permName, rtrim($perm->name, '*'))) {
                    $match = true;
                }
                if ($match && ($namespace === null || $perm->namespace === $namespace)) {
                    return true;
                }
            }
        }

        foreach ($userRes->get()->permissions()->direct() as $perm) {
            $match = false;
            if ($perm->name === $permName) {
                $match = true;
            } elseif (str_ends_with($perm->name, '*') && str_starts_with($permName, rtrim($perm->name, '*'))) {
                $match = true;
            }
            if ($match && ($namespace === null || $perm->namespace === $namespace)) {
                return true;
            }
        }

        return false;
    }

    public function can(object $user, string $permission, ...$arguments): bool
    {
        $userRes = $this->userService->user($user);
        if ($userRes->is()->superAdmin() && $this->config->superAdminBypass) {
            return true;
        }

        if ($userRes->is()->denied()->permission($permission)) {
            return false;
        }

        $hasRbac = $this->isPermitted($user, $permission);
        $result = $hasRbac;

        if (!empty($arguments)) {
            try {
                $evalResult = $this->policyRegistry->evaluate($user, $permission, ...$arguments);
                if ($evalResult instanceof \Vima\Core\Policy\DTOs\AccessResponse) {
                    if ($evalResult->shouldAbstain()) {
                        $result = $hasRbac;
                    } else {
                        $result = $evalResult->isAllowed();
                    }
                } else {
                    $result = (bool) $evalResult;
                }
            } catch (\Vima\Core\Policy\Exceptions\PolicyNotFoundException | \Vima\Core\Policy\Exceptions\PolicyMethodNotFoundException $e) {
                $result = $hasRbac;
            }
        }

        // Dispatch AuthorizationChecked Event here...
        [$namespace, $permName] = Utils::resolveNamespace($permission);
        $this->dispatcher->dispatch(new AuthorizationChecked(
            $user,
            $permName,
            $result,
            $namespace,
            $arguments
        ));

        return $result;
    }

    public function enforce(object $user, string $permission, ...$arguments): void
    {
        if (!$this->can($user, $permission, ...$arguments)) {
            // Dispatch AccessDenied Event here...
            [$namespace, $permName] = Utils::resolveNamespace($permission);
            $this->dispatcher->dispatch(new AccessDenied(
                $user,
                $permName,
                $namespace,
                $arguments
            ));
            throw new \RuntimeException("Access Denied to {$permission}");
        }
    }
}
