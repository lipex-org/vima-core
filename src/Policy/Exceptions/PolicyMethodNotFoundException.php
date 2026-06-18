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

namespace Vima\Core\Policy\Exceptions;

use RuntimeException;

/**
 * Class PolicyMethodNotFoundException
 * 
 * Thrown when a specific method is not found in a policy class.
 */
class PolicyMethodNotFoundException extends RuntimeException
{
    public function __construct(string $policyClass, string $method)
    {
        parent::__construct("Method '{$method}' not found in policy class '{$policyClass}'.");
    }
}
