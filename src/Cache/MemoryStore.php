<?php

namespace Kirameki\Cache;

use Closure;
use DateInterval;
use DateTimeInterface;

class MemoryStore extends AbstractStore
{
    protected array $stored;

    protected Closure $serializeCall;

    protected Closure $deserializeCall;

    public function __construct(?string $namespace = null, Closure $serializer = null, Closure $deserializer = null)
    {
        $this->namespace = $namespace ?? '';
        $this->stored = [];
        $this->serializeCall = $serializer ?? static fn($value) => msgpack_pack($value);
        $this->deserializeCall = $deserializer ?? static fn($data) => msgpack_unpack($data);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        if ($data = $this->fetchEntry($key)) {
            return $data['value'];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function tryGet(string $key, &$value): bool
    {
        if ($data = $this->fetchEntry($key)) {
            $value = $data['value'];
            return true;
        }
        $value = null;
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if ($data = $this->get($key)) {
                $result[$key] = $data;
            }
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        return (bool) $this->fetchEntry($key, false);
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
    public function set(string $key, $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): bool
    {
        $now = time();
        $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): array
    {
        $result = [];
        $now = time();
        foreach ($entries as $key => $value) {
            $result[$key] = $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        $value = $this->get($key) ?? 0;
        $value += $by;
        $this->set($key, $value, $this->formatTtl($ttl));
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        return $this->increment($key, -$by, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): bool
    {
        unset($this->stored[$key]);
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
        $matchedKeys = [];
        foreach($this->stored as $key => $data) {
            if (preg_match($pattern, $key)) {
                $this->remove($key);
                $matchedKeys[] = $key;
            }
        }
        return ['successful' => $matchedKeys, 'failed' => []];
    }

    /**
     * @inheritDoc
     */
    public function removeExpired(): array
    {
        $removed = [];
        foreach ($this->stored as $key => $data) {
            if ($this->ttl($key) === null) {
                $removed[$key] = $this->remove($key);
            }
        }
        return $removed;
    }

    /**
     * @inheritDoc
     */
    public function ttl(string $key): ?int
    {
        if ($data = $this->fetchEntry($key)) {
            return $this->calcRemainingTtl($data);
        }
        return null;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $this->stored = [];
        return true;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $list = [];
        foreach ($this->stored as $key => $value) {
            $list[$key] = $this->fetchEntry($key);
        }
        return $list;
    }

    /**
     * @param $key
     * @param bool $deserialize
     * @return array|null
     */
    protected function fetchEntry($key, bool $deserialize = true): ?array
    {
        if ($data = $this->stored[$key] ?? null) {
            if ($this->calcRemainingTtl($data) > 0) {
                if ($deserialize) {
                    $data['value'] = $this->deserialize($data['value']);
                }
                return $data;
            }
            // remove if expired
            $this->remove($key);
        }
        return null;
    }

    /**
     * @param string $key
     * @param $value
     * @param int|null $created
     * @param int|null $ttl
     * @return array
     */
    protected function storeEntry(string $key, $value, ?int $created, ?int $ttl): array
    {
        return $this->stored[$key] = $this->makeEntry($value, $created, $ttl);
    }

    /**
     * @param $value
     * @param int|null $created
     * @param int|null $ttl
     * @return array
     */
    protected function makeEntry($value, ?int $created, ?int $ttl): array
    {
        return [
            'value' => $this->serialize($value),
            'created' => $created,
            'ttl' => $ttl ?? INF,
        ];
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function calcRemainingTtl(array $data): bool
    {
        return ($data['created'] + $data['ttl']) - time();
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function serialize($value): mixed
    {
        return ($this->serializeCall)($value);
    }

    /**
     * @param $serialized
     * @return mixed
     */
    protected function deserialize($serialized): mixed
    {
        return ($this->deserializeCall)($serialized);
    }
}
