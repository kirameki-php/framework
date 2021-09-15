<?php declare(strict_types=1);

namespace Kirameki\Cache\Stores;

use DateInterval;
use DateTimeInterface;

class NullStore extends AbstractStore
{
    /**
     * @param string $name
     * @param string|null $namespace
     */
    public function __construct(string $name, string $namespace = null)
    {
        $this->name = $name;
        $this->namespace = $namespace ?? '';
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        if ($this->triggerEvents) {
            $this->triggerAccessEvent(__FUNCTION__, [$key], [$key => false]);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function tryGet(string $key, mixed &$value): bool
    {
        if ($this->triggerEvents) {
            $this->triggerAccessEvent(__FUNCTION__, [$key], [$key => false]);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMulti(string ...$keys): array
    {

        $results = array_fill_keys($keys, false);
        if ($this->triggerEvents) {
            $this->triggerAccessEvent(__FUNCTION__, $keys, $results);
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        if ($this->triggerEvents) {
            $this->triggerCheckEvent(__FUNCTION__, [$key => false]);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function existsMulti(string ...$keys): array
    {
        $entries = array_map(static fn($key) => false, $keys);
        if ($this->triggerEvents) {
            $this->triggerCheckEvent(__FUNCTION__, $entries);
        }
        return $entries;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, $ttl = null): void
    {
        if ($this->triggerEvents) {
            $this->triggerStoreEvent(__FUNCTION__, [$key => $value], $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        if ($this->triggerEvents) {
            $this->triggerStoreEvent(__FUNCTION__, $entries, $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        if ($this->triggerEvents) {
            $this->triggerCounterEvent(__FUNCTION__, $key, $by, null, $ttl);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        if ($this->triggerEvents) {
            $this->triggerCounterEvent(__FUNCTION__, $key, $by, null, $ttl);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        if ($this->triggerEvents) {
            $this->triggerDeleteEvent(__FUNCTION__, [$key], [$key]);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMulti(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        if ($this->triggerEvents) {
            $this->triggerDeleteEvent(__FUNCTION__, $keys, $keys);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMatched(string $pattern): array
    {
        if ($this->triggerEvents) {
            $this->triggerDeleteMatchedEvent(__FUNCTION__, $pattern, []);
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function deleteExpired(): void
    {
        if ($this->triggerEvents) {
            $this->triggerDeleteExpiredEvent(__FUNCTION__, []);
        }
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
    public function clear(): void
    {
        if ($this->triggerEvents) {
            $this->triggerClearEvent(__FUNCTION__);
        }
    }
}
