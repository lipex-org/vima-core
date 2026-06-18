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

namespace Vima\Core\Config\DTOs;

use Vima\Core\Config\Contracts\SetupProviderInterface;

/**
 * Class Setup
 * 
 * Aggregates declarative definitions of roles and permissions from providers.
 */
final class Setup
{
    /** @var \Vima\Core\Role\Entities\Role[] */
    public array $roles = [];

    /** @var \Vima\Core\Permission\Entities\Permission[] */
    public array $permissions = [];

    /**
     * @param class-string<SetupProviderInterface>[]|SetupProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $instance = is_string($provider) ? new $provider() : $provider;
            
            if (!$instance instanceof SetupProviderInterface) {
                throw new \InvalidArgumentException("Providers must implement SetupProviderInterface.");
            }

            $data = $instance->get();

            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->roles = array_merge($this->roles, $data['roles']);
            }

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->permissions = array_merge($this->permissions, $data['permissions']);
            }
        }
    }
}
