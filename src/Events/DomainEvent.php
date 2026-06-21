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

namespace Vima\Core\Events;

/**
 * Class DomainEvent
 *
 * A generic class representing runtime events fired during domain service and repository actions (roles, permissions, users).
 */
class DomainEvent extends Event
{
    public function __construct(
        private string $name,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Get the descriptive event name (e.g. 'vima.role.created').
     */
    public function getName(): string
    {
        return $this->name;
    }
}
