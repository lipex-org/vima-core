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

namespace Vima\Core\Config\Contracts;

/**
 * Interface SetupProviderInterface
 * 
 * Provides declarative role and permission definitions to the Setup configuration.
 */
interface SetupProviderInterface
{
    /**
     * Return an array defining 'roles' and 'permissions'.
     *
     * @return array{roles?: \Vima\Core\Role\Entities\Role[], permissions?: \Vima\Core\Permission\Entities\Permission[]}
     */
    public function get(): array;
}
