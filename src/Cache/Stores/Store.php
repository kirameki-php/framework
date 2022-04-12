<?php declare(strict_types=1);

namespace Kirameki\Cache\Stores;

use Closure;
use DateInterval;
use DateTimeInterface;

interface Store
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function tryGet(string $key, mixed &$value): bool;

    /**
     * @param string ...$keys
     * @return array<mixed>
     */
    public function getMulti(string ...$keys): array;

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * @param string ...$keys
     * @return array<string, bool>
     */
    public function existsMulti(string ...$keys): array;

    /**
     * @param string $key
     * @param mixed $value
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return void
     */
    public function set(string $key, $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): void;

    /**
     * @param array<mixed> $entries
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return void
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): void;

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
    public function remember(string $key, Closure $callback, DateTimeInterface|DateInterval|int|float|null $ttl = null): mixed;

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key): void;

    /**
     * @param string ...$keys
     * @return void
     */
    public function deleteMulti(string ...$keys): void;

    /**
     * @param string $pattern
     * @return array<scalar>
     */
    public function deleteMatched(string $pattern): array;

    /**
     * @return void
     */
    public function deleteExpired(): void;

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
     * @return void
     */
    public function clear(): void;

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
