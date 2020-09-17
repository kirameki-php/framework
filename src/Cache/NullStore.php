<?php

namespace Kirameki\Cache;

use Closure;

class NullStore extends AbstractStore
{
    public function __construct(string $namespace = null)
    {
        $this->namespace = $namespace ?? '';
    }

    public function get(string $key)
    {
        return null;
    }

    public function tryGet(string $key, &$value): bool
    {
        return false;
    }

    public function getMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = false;
        }
        return $result;
    }

    public function exists(string $key): bool
    {
        return false;
    }

    public function existsMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->exists($key);
        }
        return $result;
    }

    public function set(string $key, $value, $ttl = null): bool
    {
        return false;
    }

    public function setMulti(array $entries, $ttl = null): array
    {
        $result = [];
        foreach ($entries as $key => $entry) {
            $result[$key] = $this->set($key, $entry);
        }
        return $result;
    }

    public function increment(string $key, int $by = 1, $ttl = null): ?int
    {
        return null;
    }

    public function decrement(string $key, int $by = 1, $ttl = null): ?int
    {
        return null;
    }

    public function remove(string $key): bool
    {
        return true;
    }

    public function removeMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->remove($key);
        }
        return $result;
    }

    public function removeMatched(string $pattern): array
    {
        return ['successful' => [], 'failed' => []];
    }

    public function removeExpired(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function ttl(string $key): ?int
    {
        return null;
    }

    public function clear(): bool
    {
        return true;
    }
}
