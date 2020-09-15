<?php

namespace Kirameki\Cache;

use APCuIterator;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Kirameki\Support\Util;
use RuntimeException;
use function Sodium\increment;

class DeferrableStore extends AbstractStore
{
    protected StoreInterface $actual;

    protected DeferredPool $memory;

    protected bool $deferred;

    protected array $queue = [];

    protected array $afterCommitCallbacks;

    public function __construct(StoreInterface $store)
    {
        $this->actual = $store;
        $this->memory = new DeferredPool;
        $this->afterCommitCallbacks = [];
    }

    public function actual(): StoreInterface
    {
        return $this->actual;
    }

    public function memory(): DeferredPool
    {
        return $this->memory;
    }

    public function get(string $key)
    {
        $value = null;
        if ($this->deferred && $this->memory->tryGet($key, $value)) {
            return $value;
        }
        return $this->actual->get($key);
    }

    public function tryGet(string $key, &$value): bool
    {
        if ($this->deferred && $this->memory->tryGet($key, $value)) {
            return true;
        }
        return $this->actual->tryGet($key, $value);
    }

    public function getMulti(string ...$keys): array
    {
        $values = $this->memory->getMulti($keys);
        $remainingKeys = array_diff($keys, array_keys($values));
        if (!empty($remainingKeys)) {
            $fromStore = $this->actual->getMulti($remainingKeys);
            $values = array_merge($values, $fromStore);
        }
        return $values;
    }

    public function exists(string $key): bool
    {
        $result = null;
        if ($this->deferred) {
            $result = $this->memory->exists($key);
        }
        return $result || $this->actual->exists($key);
    }

    public function existsMulti(string ...$keys): array
    {
        $exists = $this->memory->existsMulti($keys);
        $remainingKeys = array_diff($keys, array_keys($exists));
        if (!empty($remainingKeys)) {
            $fromStore = $this->actual->existsMulti($remainingKeys);
            $exists = array_merge($exists, $fromStore);
        }
        return $exists;
    }

    public function set(string $key, $value, $ttl = null): bool
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, $key, $value, $this->toAbsoluteTtl($ttl));
            return $this->memory->set($key, $value, $ttl);
        }
        return $this->actual->set($key, $value, $ttl);
    }

    public function setMulti(array $entries, $ttl = null): array
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, $entries, $this->toAbsoluteTtl($ttl));
            return $this->memory->setMulti($entries, $ttl);
        }
        return $this->actual->setMulti($entries, $ttl);
    }

    /**
     * [WARNING] The returned value will not be accurate when `deferred` since
     * it will get and store the results in the pool and any updates that has
     * occurred while it's in the pool will not be reflected.
     *
     * @param string $key
     * @param int $by
     * @param null $ttl
     * @return int|null
     */
    public function increment(string $key, int $by = 1, $ttl = null): ?int
    {
        if ($this->deferred) {
            $value = null;
            if($this->memory->tryGet($key, $value)) {
                $this->memory->set($key, $value + $by, $ttl);
            }
            $this->enqueue(__FUNCTION__, $key, $by, $this->toAbsoluteTtl($ttl));
        }
        return $this->actual->increment($key, $by, $ttl);
    }

    /**
     * @see DeferrableStore::increment($key, $by, $ttl)
     *
     * @param string $key
     * @param int $by
     * @param null $ttl
     * @return int|null
     */
    public function decrement(string $key, int $by = 1, $ttl = null): ?int
    {
        if ($this->deferred) {
            $value = null;
            if($this->memory->tryGet($key, $value)) {
                $this->memory->set($key, $value - $by, $ttl);
            }
            $this->enqueue(__FUNCTION__, $key, $by, $this->toAbsoluteTtl($ttl));
        }
        return $this->actual->decrement($key, $by, $ttl);
    }

    public function ttl(string $key): ?int
    {
        $result = null;
        if ($this->deferred) {
            $result = $this->memory->ttl($key);
        }
        return $result ?? $this->actual->ttl($key);
    }

    public function remove(string $key): bool
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, $key);
            $this->memory->remove($key);
            return true;
        }
        return $this->actual->remove($key);
    }

    public function removeMulti(string ...$keys): array
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, ...$keys);
            $this->memory->removeMulti($keys);
            return [];
        }
        return $this->actual->removeMulti($keys);
    }

    public function removeMatched(string $pattern): array
    {
        if ($this->deferred) {
            throw new RuntimeException('Fuzzy matching is not supported when deferred');
        }
        return $this->actual->removeMatched($pattern);
    }

    public function removeExpired(): array
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__);
            $this->memory->removeExpired();
            return [];
        }
        return $this->actual->removeExpired();
    }

    public function clear(): bool
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__);
            $this->memory->clear();
        }
        return $this->actual->clear();
    }

    public function isDeferred(): bool
    {
        return $this->deferred;
    }

    public function hasChanges(): bool
    {
        return !empty($this->queue);
    }

    public function defer()
    {
        $this->deferred = true;
        return $this;
    }

    public function commit()
    {
        if (!$this->deferred) {
            return $this;
        }

        $results = [];
        foreach ($this->queue as $task) {
            $task['result'] = $this->executeTask($task);
            $results[] = $task;
        }
        $this->queue = [];

        $this->memory->clear();

        foreach ($this->afterCommitCallbacks as $callback) {
            $callback($this, $results);
        }

        $this->deferred = false;

        return $this;
    }

    public function afterCommit(callable $callback): void
    {
        $this->afterCommitCallbacks[] = $callback;
    }

    protected function enqueue($call, ...$args): void
    {
        $this->queue[] = compact('call', 'args');
    }

    protected function executeTask(array $task)
    {
        return $this->actual->{$task['call']}(...$task['args']);
    }

    protected function toAbsoluteTtl($ttl)
    {
        if (is_null($ttl)) return null;
        if (is_int($ttl)) return Carbon::createFromTimestamp(time() + $ttl);
        if ($ttl instanceof Carbon) return $ttl;
        if ($ttl instanceof DateTimeInterface) return Carbon::instance($ttl);
        throw new RuntimeException('Unknown type for TTL: '.Util::toString($ttl));
    }
}
