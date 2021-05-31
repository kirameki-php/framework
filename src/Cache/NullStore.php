<?php

namespace Kirameki\Cache;

use Closure;
use DateTimeInterface;

class NullStore extends AbstractStore
{
    public function __construct(string $namespace = null)
    {
        $this->namespace = $namespace ?? '';
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function tryGet(string $key, mixed &$value): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = false;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function existsMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->exists($key);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function setMulti(array $entries, DateTimeInterface|int|float|null $ttl = null): array
    {
        $result = [];
        foreach ($entries as $key => $entry) {
            $result[$key] = $this->set($key, $entry);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|int|float|null $ttl = null): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|int|float|null $ttl = null): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function removeMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->remove($key);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function removeMatched(string $pattern): array
    {
        return ['successful' => [], 'failed' => []];
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }
}
