<?php declare(strict_types=1);

namespace Kirameki\Cache\Stores;

use Closure;
use DateInterval;
use DateTimeInterface;

class MemoryStore extends AbstractStore
{
    /**
     * @var array<string, array{ value: string, created: ?int, ttl: int|float }>
     */
    protected array $stored;

    /**
     * @var Closure
     */
    protected Closure $serializeCall;

    /**
     * @var Closure
     */
    protected Closure $deserializeCall;

    /**
     * @param string $name
     * @param string|null $namespace
     * @param Closure|null $serializer
     * @param Closure|null $deserializer
     */
    public function __construct(string $name, ?string $namespace = null, Closure $serializer = null, Closure $deserializer = null)
    {
        $this->name = $name;
        $this->namespace = $namespace ?? '';
        $this->stored = [];
        $this->serializeCall = $serializer ?? $this->defaultSerializer();
        $this->deserializeCall = $deserializer ?? $this->defaultDeserializer();
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        $value = $this->fetchEntryValue($key);
        if ($this->triggerEvents) {
            $this->triggerAccessEvent(__FUNCTION__, [$key], [$key => $value]);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function tryGet(string $key, &$value): bool
    {
        $result = false;

        if ($data = $this->fetchEntry($key)) {
            $value = $data['value'];
            $result = true;
        }

        if ($this->triggerEvents) {
            $this->triggerAccessEvent(__FUNCTION__, [$key], [$key => $value]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMulti(string ...$keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if ($data = $this->fetchEntryValue($key)) {
                $result[$key] = $data;
            }
        }
        if ($this->triggerEvents) {
            $this->triggerAccessEvent(__FUNCTION__, $keys, $result);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        $result = (bool) $this->fetchEntry($key, false);
        if ($this->triggerEvents) {
            $this->triggerCheckEvent(__FUNCTION__, [$key => $result]);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function existsMulti(string ...$keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = (bool) $this->fetchEntry($key, false);
        }
        if ($this->triggerEvents) {
            $this->triggerCheckEvent(__FUNCTION__, $results);
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        $now = time();
        $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        if ($this->triggerEvents) {
            $this->triggerStoreEvent(__FUNCTION__, [$key => $value], $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        $now = time();
        foreach ($entries as $key => $value) {
            $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        }
        if ($this->triggerEvents) {
            $this->triggerStoreEvent(__FUNCTION__, $entries, $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        $value = ($this->fetchEntryValue($key) ?: 0) + $by;
        $now = time();
        $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        if ($this->triggerEvents) {
            $this->triggerCounterEvent(__FUNCTION__, $key, $by, $value, $ttl);
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        $value = ($this->fetchEntryValue($key) ?: 0) - $by;
        $now = time();
        $this->storeEntry($key, $value, $now, $this->formatTtl($ttl, $now));
        if ($this->triggerEvents) {
            $this->triggerCounterEvent(__FUNCTION__, $key, $by, $value, $ttl);
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        unset($this->stored[$key]);
        if ($this->triggerEvents) {
            $this->triggerDeleteEvent(__FUNCTION__, [$key], []);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMulti(string ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->stored[$key]);
        }
        if ($this->triggerEvents) {
            $this->triggerDeleteEvent(__FUNCTION__, $keys, []);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMatched(string $pattern): array
    {
        $matchedKeys = [];
        foreach($this->stored as $key => $data) {
            if (preg_match($pattern, $key)) {
                unset($this->stored[$key]);
                $matchedKeys[] = $key;
            }
        }
        if ($this->triggerEvents) {
            $this->triggerDeleteMatchedEvent(__FUNCTION__, $pattern, array_keys($this->stored));
        }
        return $matchedKeys;
    }

    /**
     * @inheritDoc
     */
    public function deleteExpired(): void
    {
        foreach ($this->stored as $key => $data) {
            if ($this->ttl($key) === null) {
                unset($this->stored[$key]);
            }
        }
        if ($this->triggerEvents) {
            $this->triggerDeleteExpiredEvent(__FUNCTION__, array_keys($this->stored));
        }
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
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->stored = [];
        if ($this->triggerEvents) {
            $this->triggerClearEvent(__FUNCTION__);
        }
    }

    /**
     * @return array<string, mixed>
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
     * @return Closure
     */
    protected function defaultSerializer(): Closure
    {
        if (extension_loaded('igbinary')) {
            return static fn($value) => igbinary_serialize($value);
        }

        if (extension_loaded('msgpack')) {
            return static fn($value) => msgpack_pack($value);
        }

        return static fn($value) => serialize($value);
    }

    /**
     * @return Closure
     */
    protected function defaultDeserializer(): Closure
    {
        if (extension_loaded('igbinary')) {
            return static fn($value) => igbinary_unserialize($value);
        }

        if (extension_loaded('msgpack')) {
            return static fn($value) => msgpack_unpack($value);
        }

        return static fn($value) => unserialize($value);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function fetchEntryValue(string $key): mixed
    {
        if ($data = $this->fetchEntry($key)) {
            return $data['value'];
        }
        return false;
    }

    /**
     * @param string $key
     * @param bool $deserialize
     * @return array{ value: string, created: ?int, ttl: int|float }|null
     */
    protected function fetchEntry(string $key, bool $deserialize = true): ?array
    {
        if ($data = $this->stored[$key] ?? null) {
            if ($this->calcRemainingTtl($data) > 0) {
                if ($deserialize) {
                    $data['value'] = $this->deserialize($data['value']);
                }
                return $data;
            }
            // remove if expired
            unset($this->stored[$key]);
        }
        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $created
     * @param int|null $ttl
     * @return array<string, mixed>
     */
    protected function storeEntry(string $key, mixed $value, ?int $created, ?int $ttl): array
    {
        return $this->stored[$key] = $this->makeEntry($value, $created, $ttl);
    }

    /**
     * @param mixed $value
     * @param int|null $created
     * @param int|null $ttl
     * @return array{ value: string, created: ?int, ttl: int|float }
     */
    protected function makeEntry(mixed $value, ?int $created, ?int $ttl): array
    {
        return [
            'value' => $this->serialize($value),
            'created' => $created,
            'ttl' => $ttl ?? INF,
        ];
    }

    /**
     * @param array{ value: string, created: ?int, ttl: int|float } $data
     * @return int
     */
    protected function calcRemainingTtl(array $data): int
    {
        return ($data['created'] + $data['ttl']) - time();
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function serialize(mixed $value): mixed
    {
        return ($this->serializeCall)($value);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    protected function deserialize(string $serialized): mixed
    {
        return ($this->deserializeCall)($serialized);
    }
}
