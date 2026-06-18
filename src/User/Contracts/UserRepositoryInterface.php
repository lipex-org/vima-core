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

namespace Vima\Core\User\Contracts;

interface UserRepositoryInterface
{
    public function findById(string|int $id): ?UserInterface;

    public function save(UserInterface $user): void;

    public function delete(string|int $id): void;
}
