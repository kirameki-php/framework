<?php

namespace Kirameki\Cache;

use Closure;

interface StoreInterface
{
    public function get(string $key);

    public function getMulti(string ...$keys): array;

    public function exists(string $key): bool;

    public function existsMulti(string ...$keys): array;

    public function set(string $key, $value, $ttl = null): bool;

    public function setMulti(array $entries, $ttl = null): array;

    public function increment(string $key, int $by = 1, $ttl = null): ?int;

    public function decrement(string $key, int $by = 1, $ttl = null): ?int;

    public function remember(string $key, Closure $callback, $ttl = null);

    public function remove(string $key): bool;

    public function removeMulti(string ...$keys): array;

    public function removeMatched(string $pattern): array;

    public function removeExpired(): array;

    /**
     * Returns the remaining TTL (time-to-live) of cache entry in seconds.
     * If cache is persisted, it will return `INF`.
     * If the key does not exist, it will return null;
     *
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key): ?int;

    /**
     * Clears all entries from cache
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Returns the namespace set to store
     *
     * @return string
     */
    public function namespace(): string;

    /**
     * Set bool to toggle event emitter
     *
     * @param bool $toggle
     */
    public function emitEvents(bool $toggle): void;
}
