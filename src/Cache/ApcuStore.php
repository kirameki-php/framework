<?php

namespace Kirameki\Cache;

use APCuIterator;
use Closure;

class ApcuStore extends AbstractStore
{
    protected bool $enabled;

    public function __construct(string $namespace)
    {
        $this->enabled = apcu_enabled();
        $this->namespace = $namespace;
    }

    public function get(string $key)
    {
        $success = false;
        if($this->enabled) {
            return apcu_fetch($this->formatKey($key), $success);
        }
        return null;
    }

    public function getMulti(string ...$keys): array
    {
        return $this->enabled ? apcu_fetch($this->formatKeys($keys)) : [];
    }

    public function exists(string $key): bool
    {
        return $this->enabled && apcu_exists($this->formatKey($key));
    }

    public function existsMulti(string ...$keys): array
    {
        return $this->enabled ? apcu_exists($this->formatKeys($keys)) : [];
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return $this->enabled && apcu_store($this->formatKey($key), $value, $this->formatTtl($ttl));
    }

    public function setMulti(array $entries, ?int $ttl = null): array
    {
        return $this->enabled
            ? apcu_store($entries, null, $this->formatTtl($ttl))
            : array_keys($entries);
    }

    public function increment(string $key, int $by = 1, $ttl = null): ?int
    {
        if (!$this->enabled) {
            return null;
        }
        $result = apcu_inc($key, $by, $nil, $this->formatTtl($ttl));
        return is_int($result) ? $result : null;
    }

    public function decrement(string $key, int $by = 1, $ttl = null): ?int
    {
        if (!$this->enabled) {
            return null;
        }
        $result = apcu_dec($key, $by, $nil, $this->formatTtl($ttl));
        return is_int($result) ? $result : null;
    }

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

    public function remove(string $key): bool
    {
        return $this->enabled && apcu_delete($this->formatKey($key));
    }

    public function removeMulti(string ...$keys): array
    {
        return $this->enabled ? apcu_delete($this->formatKeys($keys)) : $keys;
    }

    public function removeMatched(string $pattern): array
    {
        if (!$this->enabled) {
            return [];
        }
        $matchedKeys = $this->scan($pattern);
        $failedKeys = apcu_delete($matchedKeys);
        return ['successful' => $matchedKeys, 'failed' => $failedKeys];
    }

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

    public function clear(): bool
    {
        return $this->enabled && apcu_clear_cache();
    }

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
