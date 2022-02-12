<?php declare(strict_types=1);

namespace Kirameki\Cache\Stores;

use APCuIterator;
use Closure;
use DateInterval;
use DateTimeInterface;
use RuntimeException;

class ApcuStore extends AbstractStore
{
    /**
     * @param string $name
     * @param string $namespace
     */
    public function __construct(string $name, string $namespace)
    {
        $this->name = $name;
        $this->namespace = $namespace;

        if (!apcu_enabled()) {
            throw new RuntimeException('APCu must be enabled to use ApcuStore');
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        $success = false;
        $formattedKey = $this->formatKey($key);
        $value = apcu_fetch($formattedKey, $success);
        if ($this->triggerEvents) {
            $results = $success ? [$formattedKey => $value] : [];
            $this->triggerAccessEvent(__FUNCTION__, [$key], $results);
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function tryGet(string $key, &$value): bool
    {
        $success = false;
        $formattedKey = $this->formatKey($key);
        $value = apcu_fetch($formattedKey, $success);
        if ($this->triggerEvents) {
            $results = $success ? [$key => $value] : [];
            $this->triggerAccessEvent(__FUNCTION__, [$key], $results);
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function getMulti(string ...$keys): array
    {
        $formattedKeys = $this->formatKeys($keys);
        $entries = (array) apcu_fetch($formattedKeys) ?: [];
        $results = [];
        foreach ($formattedKeys as $formattedKey) {
            if (array_key_exists($formattedKey, $entries)) {
                $results[$this->deformatKey($formattedKey)] = $entries[$formattedKey];
            } else {
                $results[$this->deformatKey($formattedKey)] = false;
            }
        }
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
        $result = apcu_exists($this->formatKey($key));
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
        $formattedKeys = $this->formatKeys($keys);
        $entries = apcu_exists($formattedKeys);
        $results = [];
        foreach ($formattedKeys as $formattedKey) {
            $results[$this->deformatKey($formattedKey)] = $entries[$formattedKey] ?? false;
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
        $formattedKey = $this->formatKey($key);
        $formattedTtl = $this->formatTtl($ttl);
        $result = apcu_store($formattedKey, $value, $formattedTtl);
        if ($result === false) {
            throw new RuntimeException("Failed to call apcu_store($formattedKey, ...)! Something went wrong!");
        }
        if ($this->triggerEvents) {
            $this->triggerStoreEvent(__FUNCTION__, [$key => $value], $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        $formattedEntries = $this->formatEntries($entries);
        $formattedTtl = $this->formatTtl($ttl);
        $result = apcu_store($formattedEntries, null, $formattedTtl);
        if (!empty($result)) {
            $formattedKeysString = implode(', ', array_keys($formattedEntries));
            throw new RuntimeException("Failed to call apcu_store([$formattedKeysString], ...)! Something went wrong!");
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
        $success = false;
        $formattedKey = $this->formatKey($key);
        $formattedTtl = $this->formatTtl($ttl);
        $result = apcu_inc($formattedKey, $by, $success, $formattedTtl);
        if (!$success) {
            throw new RuntimeException("Failed to call apcu_inc('$formattedKey', $by)! Something went wrong!");
        }
        if ($this->triggerEvents) {
            $this->triggerCounterEvent(__FUNCTION__, $key, $by, $result, $ttl);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        $success = false;
        $formattedKey = $this->formatKey($key);
        $formattedTtl = $this->formatTtl($ttl);
        $result = apcu_dec($formattedKey, $by, $success, $formattedTtl);
        if (!$success) {
            throw new RuntimeException("Failed to call apcu_inc('$formattedKey', $by)! Something went wrong!");
        }
        if ($this->triggerEvents) {
            $this->triggerCounterEvent(__FUNCTION__, $key, $by, $result, $ttl);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function ttl(string $key): ?int
    {
        if ($data = apcu_key_info($key)) {
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
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        $missedKey = apcu_delete($this->formatKey($key));
        if ($this->triggerEvents) {
            $this->triggerDeleteEvent(__FUNCTION__, [$key], $missedKey ? [] : [$key]);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMulti(string ...$keys): void
    {
        $missedKeys = apcu_delete($this->formatKeys($keys));
        if ($this->triggerEvents) {
            $this->triggerDeleteEvent(__FUNCTION__, $keys, $missedKeys);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMatched(string $pattern): array
    {
        $matchedKeys = $this->scan($pattern);
        apcu_delete($matchedKeys);
        if ($this->triggerEvents) {
            $this->triggerDeleteMatchedEvent(__FUNCTION__, $pattern, $matchedKeys);
        }
        return $matchedKeys;
    }

    /**
     * @inheritDoc
     */
    public function deleteExpired(): void
    {
        $now = time();
        $format = APC_ITER_KEY | APC_ITER_CTIME | APC_ITER_TTL;
        $keys = $this->scan('', $format, static function(array $data) use ($now) {
            if ($data['ttl'] > 0) {
                $expires = (int) ($data['creation_time'] + $data['ttl']);
                return $expires <= $now;
            }
            return false;
        });
        apcu_delete($keys);
        if ($this->triggerEvents) {
            $this->triggerDeleteExpiredEvent(__FUNCTION__, $keys);
        }
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $results = apcu_clear_cache();
        if ($this->triggerEvents) {
            $this->triggerClearEvent(__FUNCTION__);
        }
        if (!$results) {
            throw new RuntimeException('Clearing APCu failed');
        }
    }

    /**
     * @param string $search
     * @param int $format
     * @param Closure|null $filter
     * @return array<int, string>
     */
    protected function scan(string $search, int $format = APC_ITER_KEY, Closure $filter = null): array
    {
        $keys = [];
        $search = $this->formatKey($search);
        foreach (new APCuIterator($search, $format) as $data) {
            if (is_array($data) && $filter !== null && $filter($data) !== false) {
                $keys[] = (string) $data['key'];
            }
        }
        return $keys;
    }

    /**
     * @param DateTimeInterface|DateInterval|int|float|null $ttl
     * @param int|null $now
     * @return int
     */
    protected function formatTtl(DateTimeInterface|DateInterval|int|float|null $ttl = null, int $now = null): int
    {
        // for APCu, 0 === persist
        if ($ttl === null || $ttl === INF) {
            return 0;
        }
        $result = parent::formatTtl($ttl);
        return $result === 0 ? $result : -1;
    }
}
