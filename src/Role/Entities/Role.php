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

namespace Vima\Core\Role\Entities;

use Vima\Core\Support\Utils\Utils;

/**
 * Class Role
 * 
 * Represents a set of permissions or a user role.
 */
class Role
{
    /**
     * Transient property used during configuration/syncing to define assigned permissions.
     * @var string[]|\Vima\Core\Permission\Entities\Permission[]
     */
    public array $permissions = [];

    /**
     * Transient property used during configuration/syncing to define parent roles.
     * @var string[]
     */
    public array $parents = [];

    /**
     * Transient property used during configuration/syncing to define child roles.
     * @var string[]
     */
    public array $children = [];

    /**
     * @param string $name
     * @param string|null $namespace
     * @param string|null $description
     * @param array $context
     * @param int|string|null $id
     */
    public function __construct(
        public string $name,
        public ?string $namespace = null,
        public ?string $description = null,
        public array $context = [],
        public int|string|null $id = null
    ) {
        if (str_contains($this->name, ':')) {
            [$ns, $n] = Utils::resolveNamespace($this->name);
            $this->namespace = $ns;
            $this->name = $n;
        }
    }

    /**
     * Fluent factory method to define a role for the Setup config.
     */
    public static function define(string $name, ?string $namespace = null, ?string $description = null): self
    {
        return new self($name, namespace: $namespace, description: $description);
    }

    public function withPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function withParents(array $parents): self
    {
        $this->parents = $parents;
        return $this;
    }

    public function withChildren(array $children): self
    {
        $this->children = $children;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->namespace ? "{$this->namespace}:{$this->name}" : $this->name;
    }
}
