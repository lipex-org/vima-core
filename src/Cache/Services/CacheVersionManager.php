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

namespace Vima\Core\Cache\Services;

use Vima\Core\Cache\Contracts\CacheInterface;
use Vima\Core\Config\VimaConfig;

/**
 * Class CacheVersionManager
 *
 * Handles version-epoch based cache key generation and granular invalidation.
 */
class CacheVersionManager
{
    /**
     * In-memory L1 cache store for fast intra-request lookups.
     * @var array<string, mixed>
     */
    private static array $l1Store = [];

    public function __construct(
        private ?CacheInterface $cache = null,
        private ?VimaConfig $config = null
    ) {
    }

    public function isCacheEnabled(): bool
    {
        return $this->config !== null && $this->config->cacheEnabled && $this->cache !== null;
    }

    public function getPrefix(): string
    {
        if ($this->config === null) {
            return 'vima';
        }
        return rtrim($this->config->cachePrefix, '_:') ?: 'vima';
    }

    public function getTTL(): int
    {
        return $this->config?->cacheTTL ?? 3600;
    }

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    /**
     * Get the global schema/state version epoch.
     */
    public function getGlobalEpoch(): int
    {
        if (!$this->isCacheEnabled()) {
            return 1;
        }

        $key = $this->getPrefix() . '_epoch_global';
        $epoch = $this->cache->get($key);

        if ($epoch === null || !is_numeric($epoch)) {
            $this->cache->set($key, 1, $this->getTTL());
            return 1;
        }

        return (int) $epoch;
    }

    /**
     * Increment the global epoch. Instantly invalidates all Vima caches globally.
     */
    public function bumpGlobalEpoch(): int
    {
        self::clearL1();

        if (!$this->isCacheEnabled()) {
            return 1;
        }

        $current = $this->getGlobalEpoch();
        $next = $current + 1;
        $key = $this->getPrefix() . '_epoch_global';
        $this->cache->set($key, $next, $this->getTTL());

        return $next;
    }

    /**
     * Get the user version epoch.
     */
    public function getUserEpoch(int|string $userId): int
    {
        if (!$this->isCacheEnabled()) {
            return 1;
        }

        $key = $this->getPrefix() . '_epoch_user_' . $userId;
        $epoch = $this->cache->get($key);

        if ($epoch === null || !is_numeric($epoch)) {
            return 1;
        }

        return (int) $epoch;
    }

    /**
     * Increment the user's version epoch. Instantly invalidates all permissions/roles for this user.
     */
    public function bumpUserEpoch(int|string $userId): int
    {
        self::clearL1User($userId);

        if (!$this->isCacheEnabled()) {
            return 1;
        }

        $current = $this->getUserEpoch($userId);
        $next = $current + 1;
        $key = $this->getPrefix() . '_epoch_user_' . $userId;
        $this->cache->set($key, $next, $this->getTTL());

        return $next;
    }

    /**
     * Get the role version epoch.
     */
    public function getRoleEpoch(int|string $roleId): int
    {
        if (!$this->isCacheEnabled()) {
            return 1;
        }

        $key = $this->getPrefix() . '_epoch_role_' . $roleId;
        $epoch = $this->cache->get($key);

        if ($epoch === null || !is_numeric($epoch)) {
            return 1;
        }

        return (int) $epoch;
    }

    /**
     * Increment the role's version epoch. Instantly invalidates this role's permission tree.
     */
    public function bumpRoleEpoch(int|string $roleId): int
    {
        self::clearL1();

        if (!$this->isCacheEnabled()) {
            return 1;
        }

        $current = $this->getRoleEpoch($roleId);
        $next = $current + 1;
        $key = $this->getPrefix() . '_epoch_role_' . $roleId;
        $this->cache->set($key, $next, $this->getTTL());

        return $next;
    }

    /**
     * Compute a composite hash of all role epochs given an array of role IDs.
     *
     * @param array<int|string> $roleIds
     */
    public function getRolesHash(array $roleIds): string
    {
        if (empty($roleIds)) {
            return 'noroles';
        }

        sort($roleIds);
        $epochs = [];
        foreach ($roleIds as $rId) {
            $epochs[$rId] = $this->getRoleEpoch($rId);
        }

        return substr(md5(json_encode($epochs)), 0, 10);
    }

    /**
     * Build an epoch-versioned cache key for a user item.
     *
     * @param int|string $userId
     * @param string $type (e.g. 'roles', 'compiled', 'matrix')
     * @param array<int|string> $roleIds
     * @param string $extra Extra context/filter identifier
     */
    public function buildUserKey(int|string $userId, string $type, array $roleIds = [], string $extra = ''): string
    {
        $prefix = $this->getPrefix();
        $global = $this->getGlobalEpoch();
        $userEpoch = $this->getUserEpoch($userId);
        $rolesHash = $this->getRolesHash($roleIds);

        $suffix = $extra !== '' ? '_' . $extra : '';

        return "{$prefix}_v{$global}_u{$userId}_{$type}_e{$userEpoch}_r{$rolesHash}{$suffix}";
    }

    /**
     * Build an epoch-versioned cache key for a role permission tree.
     *
     * @param int|string $roleId
     */
    public function buildRoleKey(int|string $roleId): string
    {
        $prefix = $this->getPrefix();
        $global = $this->getGlobalEpoch();
        $roleEpoch = $this->getRoleEpoch($roleId);

        return "{$prefix}_v{$global}_role_{$roleId}_e{$roleEpoch}";
    }

    /**
     * Store item in L1 (in-memory) cache.
     */
    public static function setL1(string $key, mixed $value): void
    {
        self::$l1Store[$key] = $value;
    }

    /**
     * Get item from L1 (in-memory) cache.
     */
    public static function getL1(string $key): mixed
    {
        return self::$l1Store[$key] ?? null;
    }

    /**
     * Check if item exists in L1 (in-memory) cache.
     */
    public static function hasL1(string $key): bool
    {
        return array_key_exists($key, self::$l1Store);
    }

    /**
     * Clear all L1 in-memory cache.
     */
    public static function clearL1(): void
    {
        self::$l1Store = [];
    }

    /**
     * Clear L1 in-memory cache for a specific user.
     */
    public static function clearL1User(int|string $userId): void
    {
        $prefix = "u_{$userId}_";
        foreach (array_keys(self::$l1Store) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset(self::$l1Store[$key]);
            }
        }
    }
}
