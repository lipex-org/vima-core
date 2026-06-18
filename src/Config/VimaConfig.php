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

namespace Vima\Core\Config;

use Closure;
use Vima\Core\Config\Schema\Tables;
use Vima\Core\Config\Schema\Columns;
use Vima\Core\Config\DTOs\Setup;
use Vima\Core\Config\DTOs\UserMethods;

/**
 * Class VimaConfig
 * 
 * Main configuration object for Vima Core.
 */
class VimaConfig
{
    /**
     * @param Tables $tables
     * @param Columns $columns
     * @param Setup $setup
     * @param UserMethods $userMethods
     * @param Closure|null $registerPolicies
     * @param Closure|null $userResolver
     * @param bool $cacheEnabled
     * @param int $cacheTTL
     * @param string $cachePrefix
     * @param mixed $superAdminRole
     * @param bool $superAdminBypass
     */
    public function __construct(
        public Tables $tables = new Tables(),
        public Columns $columns = new Columns(),
        public Setup $setup = new Setup(),
        public UserMethods $userMethods = new UserMethods(),

        public ?Closure $registerPolicies = null,
        public ?Closure $userResolver = null,

        public bool $cacheEnabled = false,
        public int $cacheTTL = 3600,
        public string $cachePrefix = 'vima_',

        public mixed $superAdminRole = null,
        public bool $superAdminBypass = true,
    ) {
        if ($this->registerPolicies) {
            ($this->registerPolicies)();
        }
    }
}
