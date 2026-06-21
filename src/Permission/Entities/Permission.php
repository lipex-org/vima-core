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

namespace Vima\Core\Permission\Entities;

use Vima\Core\Support\Utils\Utils;

/**
 * Class Permission
 * 
 * Represents an individual permission or ability.
 */
class Permission
{
    /**
     * @param string $name
     * @param string|null $namespace
     * @param string|null $description
     * @param int|string|null $id
     * @param array $constraints
     * @param array $context
     */
    public function __construct(
        public string $name,
        public ?string $namespace = null,
        public ?string $description = null,
        public int|string|null $id = null,
        public array $constraints = [],
        public array $context = [],
    ) {
        if (str_contains($this->name, ':')) {
            [$ns, $n] = Utils::resolveNamespace($this->name);
            $this->namespace = $ns;
            $this->name = $n;
        }
    }

    /**
     * Fluent factory method to define a permission for the Setup config.
     */
    public static function define(string $name, ?string $namespace = null, ?string $description = null): self
    {
        return new self($name, namespace: $namespace, description: $description);
    }

    public function getFullName(): string
    {
        return $this->namespace ? "{$this->namespace}:{$this->name}" : $this->name;
    }
}
