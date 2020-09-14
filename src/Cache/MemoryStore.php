<?php

namespace Kirameki\Cache;

use Closure;

class MemoryStore extends AbstractStore
{
    protected array $stored;

    protected ?Closure $serializeCall;

    protected ?Closure $deserializeCall;

    public function __construct(?string $namespace = null, Closure $serializer = null, Closure $deserializer = null)
    {
        $this->namespace = $namespace ?? '';
        $this->serializeCall = $serializer;
        $this->deserializeCall = $deserializer;
        $this->stored = [];
    }

    public function get(string $key)
    {
        if ($data = $this->extractData($key)) {
            return $this->deserialize($data['value']);
        }
        return null;
    }

    public function getMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if ($this->exists($key)) {
                $result[$key] = $this->stored[$key];
            }
        }
        return $result;
    }

    public function exists(string $key): bool
    {
        return (bool) $this->extractData($key);
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
        $this->stored[$key] = $this->createData($value, $now, $this->formatTtl($ttl, $now));
        return true;
    }

    public function setMulti(array $entries, $ttl = null): array
    {
        $result = [];
        $now = time();
        foreach ($entries as $key => $value) {
            $result[$key] = $this->stored[$key] = $this->createData($value, $now, $this->formatTtl($ttl, $now));
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
        $value = $this->get($key) ?? 0;
        $value -= $by;
        $this->set($key, $value, $this->formatTtl($ttl));
        return $value;
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

    public function ttl(string $key): ?int
    {
        if ($data = $this->extractData($key)) {
            $remains = $this->calcRemainingTtl($data);
            if ($remains > 0) {
                return $remains;
            }
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
            $data = $this->extractData($key);
            $data['value'] = $this->deserialize($data['value']);
            $list[$key] = $data;
        }
        return $list;
    }

    protected function extractData($key): ?array
    {
        if ($data = $this->stored[$key] ?? null) {
            if ($this->calcRemainingTtl($data) > 0) {
                return $data;
            }
            // remove if expired
            $this->remove($key);
        }
        return null;
    }

    protected function createData($value, ?int $created, ?int $ttl): array
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
            : msgpack_pack($serialized);
    }
}
