<?php

declare(strict_types=1);

namespace Vima\Core\Tests\Fixtures\MockRepositories;

use Vima\Core\User\Contracts\UserDenyRepositoryInterface;
use Vima\Core\User\Entities\UserDeny;
use DateTimeInterface;

class ArrayUserDenyRepository implements UserDenyRepositoryInterface
{
    private array $denies = [];
    private int $nextId = 1;

    public function add(string|int $userId, string|int $permissionId, ?string $reason = null, ?DateTimeInterface $expiresAt = null): void
    {
        $this->remove($userId, $permissionId); // prevent duplicates
        
        $deny = new UserDeny(
            id: $this->nextId++,
            userId: $userId,
            permissionId: $permissionId,
            reason: $reason,
            expiresAt: $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
            createdAt: date('Y-m-d H:i:s')
        );
        
        $this->denies[$deny->id] = $deny;
    }

    public function remove(string|int $userId, string|int $permissionId): void
    {
        foreach ($this->denies as $id => $deny) {
            if ($deny->userId == $userId && $deny->permissionId == $permissionId) {
                unset($this->denies[$id]);
                return;
            }
        }
    }

    public function isDenied(string|int $userId, string|int $permissionId): bool
    {
        foreach ($this->denies as $deny) {
            if ($deny->userId == $userId && $deny->permissionId == $permissionId) {
                if ($deny->expiresAt && strtotime($deny->expiresAt) < time()) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }

    public function getDeniedPermissions(string|int $userId): array
    {
        return array_values(array_filter($this->denies, fn(UserDeny $ud) => $ud->userId == $userId));
    }
}
