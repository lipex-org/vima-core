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
use Vima\Core\Role\Entities\Role;
use Vima\Core\Support\Utils\Utils;
use Vima\Core\Config\VimaConfig;

class UserHas
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private UserGet $userGet,
        private UserIsDenied $userIsDenied,
        private VimaConfig $config
    ) {
    }

    public function role(string|Role $role, array $context = []): bool
    {
        if ($this->userIsDenied->role($role)) {
            return false;
        }

        // Super Admin bypass: if enabled and the user is a super admin, they have all roles.
        $superAdminRole = $this->config->superAdminRole;
        $roleNameString = is_string($role) ? $role : $role->name;
        if ($superAdminRole && $this->config->superAdminBypass && $roleNameString !== $superAdminRole) {
            if ($this->role($superAdminRole)) {
                return true;
            }
        }

        $roleName = '';
        $roleNamespace = null;

        if (is_string($role)) {
            [$roleNamespace, $roleName] = Utils::resolveNamespace($role);
        } elseif ($role instanceof Role) {
            $roleName = $role->name;
            $roleNamespace = $role->namespace;
        }

        $roles = $this->userGet->roles(false);

        foreach ($roles as $r) {
            if ($r->name !== $roleName || $r->namespace !== $roleNamespace) {
                continue;
            }

            if (!empty($context)) {
                $matches = true;
                foreach ($context as $k => $v) {
                    if (!isset($r->context[$k]) || $r->context[$k] != $v) {
                        $matches = false;
                        break;
                    }
                }
                if (!$matches) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }
}
