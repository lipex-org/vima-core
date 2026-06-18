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

namespace Vima\Core\User\Fluent;

use Vima\Core\Role\Services\RoleService;
use Vima\Core\Role\Entities\Role;
use Vima\Core\Support\Utils\Utils;

class UserHas
{
    public function __construct(
        private int|string $userId,
        private RoleService $roleService,
        private UserGet $userGet,
        private UserIsDenied $userIsDenied
    ) {
    }

    public function role(string|Role $role, array $context = []): bool
    {
        if ($this->userIsDenied->role($role)) {
            return false;
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
