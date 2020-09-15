<?php

namespace Kirameki\Cache;

use Closure;

class MemoryStore extends AbstractStore
{
    protected array $stored;

    protected ?Closure $serializeCall;

    protected ?Closure $deserializeCall;

    public function __construct(?string $namespace = null, array $options = [])
    {
        $this->namespace = $namespace ?? '';
        $this->stored = [];
        $this->serializeCall = $options['serializer'] ?? null;
        $this->deserializeCall = $options['deserializer'] ?? null;
    }

    public function get(string $key)
    {
        if ($data = $this->fetchEntry($key)) {
            return $data['value'];
        }
        return null;
    }

    public function tryGet(string $key, &$value): bool
    {
        if ($data = $this->fetchEntry($key)) {
            $value = $data['value'];
            return true;
        }
        $value = null;
        return false;
    }

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

    public function exists(string $key): bool
    {
        return (bool) $this->fetchEntry($key, false);
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
        $now = time();
        $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        return true;
    }

    public function setMulti(array $entries, $ttl = null): array
    {
        $result = [];
        $now = time();
        foreach ($entries as $key => $value) {
            $result[$key] = $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        }
        return $result;
    }

    public function increment(string $key, int $by = 1, $ttl = null): ?int
    {
        $value = $this->get($key) ?? 0;
        $value += $by;
        $this->set($key, $value, $this->formatTtl($ttl));
        return $value;
    }

    public function decrement(string $key, int $by = 1, $ttl = null): ?int
    {
        return $this->increment($key, -$by, $ttl);
    }

    public function remove(string $key): bool
    {
        unset($this->stored[$key]);
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
        $matchedKeys = [];
        foreach($this->stored as $key => $data) {
            if (preg_match($pattern, $key)) {
                $this->remove($key);
                $matchedKeys[] = $key;
            }
        }
        return ['successful' => $matchedKeys, 'failed' => []];
    }

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

    public function clear(): bool
    {
        $this->stored = [];
        return true;
    }

    public function all(): array
    {
        $list = [];
        foreach ($this->stored as $key => $value) {
            $list[$key] = $this->fetchEntry($key);
        }
        return $list;
    }

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

    protected function storeEntry(string $key, $value, ?int $created, ?int $ttl): array
    {
        return $this->stored[$key] = $this->makeEntry($value, $created, $ttl);
    }

    protected function makeEntry($value, ?int $created, ?int $ttl): array
    {
        return [
            'value' => $this->serialize($value),
            'created' => $created,
            'ttl' => $ttl ?? INF,
        ];
    }

    protected function calcRemainingTtl(array $data): bool
    {
        return ($data['created'] + $data['ttl']) - time();
    }

    protected function serialize($value)
    {
        return $this->serializeCall !== null
            ? call_user_func($this->serializeCall, $value)
            : msgpack_pack($value);
    }

    protected function deserialize($serialized)
    {
        return $this->deserializeCall !== null
            ? call_user_func($this->deserializeCall, $serialized)
            : msgpack_unpack($serialized);
    }
}
