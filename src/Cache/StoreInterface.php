<?php

namespace Kirameki\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;

interface StoreInterface
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * @param string $key
     * @param $value
     * @return bool
     */
    public function tryGet(string $key, &$value): bool;

    /**
     * @param string ...$keys
     * @return array
     */
    public function getMulti(string ...$keys): array;

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * @param string ...$keys
     * @return array
     */
    public function existsMulti(string ...$keys): array;

    /**
     * @param string $key
     * @param $value
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return bool
     */
    public function set(string $key, $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): bool;

    /**
     * @param array $entries
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return array
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): array;

    /**
     * @param string $key
     * @param int $by
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return int|null
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int;

    /**
     * @param string $key
     * @param int $by
     * @param DateTimeInterface|DateInterval|float|int|null $ttl
     * @return int|null
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int;

    /**
     * @param string $key
     * @param Closure $callback
     * @param DateTimeInterface|DateInterval|float|int|null $ttl
     * @return mixed
     */
    public function remember(string $key, Closure $callback, DateTimeInterface|DateInterval|int|float|null $ttl = null);

    /**
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool;

    /**
     * @param string ...$keys
     * @return array
     */
    public function removeMulti(string ...$keys): array;

    /**
     * @param string $pattern
     * @return array
     */
    public function removeMatched(string $pattern): array;

    /**
     * @return array
     */
    public function removeExpired(): array;

    /**
     * Returns the remaining TTL (time-to-live) of cache entry in seconds.
     * If cache is persisted, it will return `INF`.
     * If the key does not exist, it will return null;
     *
     * @param string $key
     * @return float|int|null
     */
    public function ttl(string $key): int|float|null;

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
