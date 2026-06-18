<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\User\Contracts\UserRoleDenyRepositoryInterface;
use Vima\Core\User\Entities\UserRoleDeny;
use DateTimeInterface;

class ArrayUserRoleDenyRepository implements UserRoleDenyRepositoryInterface
{
    private array $denies = [];
    private int $nextId = 1;

    public function add(string|int $userId, string|int $roleId, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        $this->remove($userId, $roleId);
        
        $deny = new UserRoleDeny(
            id: $this->nextId++,
            userId: $userId,
            roleId: $roleId,
            reason: $reason,
            expiresAt: $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
            createdAt: date('Y-m-d H:i:s')
        );
        
        $this->denies[$deny->id] = $deny;
    }

    public function remove(string|int $userId, string|int $roleId): void
    {
        foreach ($this->denies as $id => $deny) {
            if ($deny->userId == $userId && $deny->roleId == $roleId) {
                unset($this->denies[$id]);
                return;
            }
        }
    }

    public function isDenied(string|int $userId, string|int $roleId): bool
    {
        foreach ($this->denies as $deny) {
            if ($deny->userId == $userId && $deny->roleId == $roleId) {
                if ($deny->expiresAt && strtotime($deny->expiresAt) < time()) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }

    public function getDeniedRoles(string|int $userId): array
    {
        return array_values(array_filter($this->denies, fn(UserRoleDeny $urd) => $urd->userId == $userId));
    }
}
