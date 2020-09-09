<?php

namespace Kirameki\Cache;

use Closure;

interface StoreInterface
{
    public function get(string $key);

    public function tryGet(string $key, &$value): bool;

    public function getMulti(string ...$keys): array;

    public function set(string $key, $value, ?int $ttl = null): bool;

    public function setMulti(array $entries, ?int $ttl = null): array;

    public function incr(string $key, int $by = 1, int $ttl = 0);

    public function decr(string $key, int $by = 1, int $ttl = 0);

    public function remember(string $key, Closure $callback, ?int $ttl = null);

    public function remove(string $key): bool;

    public function removeMulti(string ...$keys): array;

    public function removeMatched(string $match): array;

    public function removeExpired(): array;

    public function exist(string $key): bool;

    public function clear(): bool;

    public function namespace(): string;

    public function triggerEvents(bool $toggle): void;
}
