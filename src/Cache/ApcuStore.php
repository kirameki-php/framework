<?php

namespace Kirameki\Cache;

use APCuIterator;
use Closure;
use DateInterval;
use DateTimeInterface;

class ApcuStore extends AbstractStore
{
    /**
     * @var bool
     */
    protected bool $enabled;

    /**
     * @param string $namespace
     */
    public function __construct(string $namespace)
    {
        $this->enabled = apcu_enabled();
        $this->namespace = $namespace;
    }

    /**
     * @param string $key
     * @return false|mixed|null
     */
    public function get(string $key): mixed
    {
        $success = false;
        if($this->enabled) {
            $value = apcu_fetch($this->formatKey($key), $success);
            return $success ? $value : null;
        }
        return null;
    }

    /**
     * @param string $key
     * @param $value
     * @return bool
     */
    public function tryGet(string $key, &$value): bool
    {
        if($this->enabled) {
            $success = false;
            $value = apcu_fetch($this->formatKey($key), $success);
            if (!$success) {
                $value = null;
            }
            return $success;
        }
        $value = null;
        return false;
    }

    /**
     * @param string ...$keys
     * @return array
     */
    public function getMulti(string ...$keys): array
    {
        return $this->enabled ? apcu_fetch($this->formatKeys($keys)) : [];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->enabled && apcu_exists($this->formatKey($key));
    }

    /**
     * @param string ...$keys
     * @return array
     */
    public function existsMulti(string ...$keys): array
    {
        return $this->enabled ? apcu_exists($this->formatKeys($keys)) : [];
    }

    /**
     * @param string $key
     * @param $value
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return bool
     */
    public function set(string $key, $value, DateTimeInterface|DateInterval|int|float|null $ttl = null): bool
    {
        return $this->enabled && apcu_store($this->formatKey($key), $value, $this->formatTtl($ttl));
    }

    /**
     * @param array $entries
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return array
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): array
    {
        return $this->enabled
            ? apcu_store($entries, null, $this->formatTtl($ttl))
            : array_keys($entries);
    }

    /**
     * @param string $key
     * @param int $by
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return int|null
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        if (!$this->enabled) {
            return null;
        }
        $result = apcu_inc($key, $by, $nil, $this->formatTtl($ttl));
        return is_int($result) ? $result : null;
    }

    /**
     * @param string $key
     * @param int $by
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @return int|null
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        if (!$this->enabled) {
            return null;
        }
        $result = apcu_dec($key, $by, $nil, $this->formatTtl($ttl));
        return is_int($result) ? $result : null;
    }

    /**
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key): ?int
    {
        if ($this->enabled && $data = apcu_key_info($key)) {
            if ($data['ttl'] === 0) {
                return INF;
            }
            $ttl = ($data['creation_time'] + $data['ttl']) - time();
            if ($ttl > 0) {
                return $ttl;
            }
        }
        return null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool
    {
        return $this->enabled && apcu_delete($this->formatKey($key));
    }

    /**
     * @param string ...$keys
     * @return string[]
     */
    public function removeMulti(string ...$keys): array
    {
        return $this->enabled ? apcu_delete($this->formatKeys($keys)) : $keys;
    }

    /**
     * @param string $pattern
     * @return array
     */
    public function removeMatched(string $pattern): array
    {
        if (!$this->enabled) {
            return [];
        }
        $matchedKeys = $this->scan($pattern);
        $failedKeys = apcu_delete($matchedKeys);
        return ['successful' => $matchedKeys, 'failed' => $failedKeys];
    }

    /**
     * @return array
     */
    public function removeExpired(): array
    {
        if (!$this->enabled) {
            return [];
        }
        $now = time();
        $format = APC_ITER_KEY | APC_ITER_CTIME | APC_ITER_TTL;
        $matchedKeys = $this->scan('', $format, static function(array $data) use ($now) {
            if ($data['ttl'] > 0) {
                $expires = $data['creation_time'] + $data['ttl'];
                return $expires <= $now;
            }
            return false;
        });
        $failedKeys = apcu_delete($matchedKeys);
        return array_diff($matchedKeys, $failedKeys);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return $this->enabled && apcu_clear_cache();
    }

    /**
     * @param string $search
     * @param int $format
     * @param Closure|null $filter
     * @return array
     */
    protected function scan(string $search, int $format = APC_ITER_KEY, Closure $filter = null): array
    {
        $keys = [];
        $search = $this->formatKey($search);
        foreach (new APCuIterator($search, $format) as $data) {
            if ($filter !== null && $filter($data) !== false) {
                $keys[] = $data['key'];
            }
        }
        return $keys;
    }}
