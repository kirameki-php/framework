<?php

namespace Kirameki\Cache\Stores;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use Kirameki\Support\Util;
use RuntimeException;

class DeferrableStore extends AbstractStore
{
    /**
     * @var StoreInterface
     */
    protected StoreInterface $actual;

    /**
     * @var DeferredPool
     */
    protected DeferredPool $memory;

    /**
     * @var bool
     */
    protected bool $deferred;

    /**
     * @var array
     */
    protected array $queue = [];

    /**
     * @var array
     */
    protected array $afterCommitCallbacks;

    /**
     * @param StoreInterface $store
     */
    public function __construct(StoreInterface $store)
    {
        $this->actual = $store;
        $this->memory = new DeferredPool;
        $this->afterCommitCallbacks = [];
    }

    /**
     * @return StoreInterface
     */
    public function actual(): StoreInterface
    {
        return $this->actual;
    }

    /**
     * @return DeferredPool
     */
    public function memory(): DeferredPool
    {
        return $this->memory;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        $value = null;
        if ($this->deferred && $this->memory->tryGet($key, $value)) {
            return $value;
        }
        return $this->actual->get($key);
    }

    /**
     * @inheritDoc
     */
    public function tryGet(string $key, &$value): bool
    {
        if ($this->deferred && $this->memory->tryGet($key, $value)) {
            return true;
        }
        return $this->actual->tryGet($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function getMulti(string ...$keys): array
    {
        $values = $this->memory->getMulti(...$keys);
        $remainingKeys = array_diff($keys, array_keys($values));
        if (!empty($remainingKeys)) {
            $fromStore = $this->actual->getMulti(...$remainingKeys);
            $values = array_merge($values, $fromStore);
        }
        return $values;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $key): bool
    {
        $result = null;
        if ($this->deferred) {
            $result = $this->memory->exists($key);
        }
        return $result || $this->actual->exists($key);
    }

    /**
     * @inheritDoc
     */
    public function existsMulti(string ...$keys): array
    {
        $exists = $this->memory->existsMulti(...$keys);
        $remainingKeys = array_diff($keys, array_keys($exists));
        if (!empty($remainingKeys)) {
            $fromStore = $this->actual->existsMulti(...$remainingKeys);
            $exists = array_merge($exists, $fromStore);
        }
        return $exists;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, $ttl = null): void
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, $key, $value, $this->toAbsoluteTtl($ttl));
            $this->memory->set($key, $value, $ttl);
        } else {
            $this->actual->set($key, $value, $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function setMulti(array $entries, DateTimeInterface|DateInterval|int|float|null $ttl = null): void
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, $entries, $this->toAbsoluteTtl($ttl));
            $this->memory->setMulti($entries, $ttl);
        } else {
            $this->actual->setMulti($entries, $ttl);
        }
    }

    /**
     * [WARNING] The returned value will not be accurate when `deferred` since
     * it will get and store the results in the pool and any updates that has
     * occurred while it's in the pool will not be reflected.
     *
     * @inheritDoc
     */
    public function increment(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        if ($this->deferred) {
            $this->memory->set($key, ($this->get($key) ?? 0) + $by, $ttl);
            $this->enqueue(__FUNCTION__, $key, $by, $this->toAbsoluteTtl($ttl));
        }
        return $this->actual->increment($key, $by, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function decrement(string $key, int $by = 1, DateTimeInterface|DateInterval|int|float|null $ttl = null): ?int
    {
        if ($this->deferred) {
            $this->memory->set($key, ($this->get($key) ?? 0) - $by, $ttl);
            $this->enqueue(__FUNCTION__, $key, $by, $this->toAbsoluteTtl($ttl));
        }
        return $this->actual->decrement($key, $by, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function ttl(string $key): ?int
    {
        $result = null;
        if ($this->deferred) {
            $result = $this->memory->ttl($key);
        }
        return $result ?? $this->actual->ttl($key);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, $key);
            $this->memory->delete($key);
        } else {
            $this->actual->delete($key);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMulti(string ...$keys): void
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__, ...$keys);
            $this->memory->deleteMulti(...$keys);
        } else {
            $this->actual->deleteMulti(...$keys);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMatched(string $pattern): array
    {
        if ($this->deferred) {
            throw new RuntimeException('Fuzzy matching is not supported when deferred');
        }
        return $this->actual->deleteMatched($pattern);
    }

    /**
     * @inheritDoc
     */
    public function deleteExpired(): void
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__);
            $this->memory->deleteExpired();
        } else {
            $this->actual->deleteExpired();
        }
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        if ($this->deferred) {
            $this->enqueue(__FUNCTION__);
            $this->memory->clear();
        }
        $this->actual->clear();
    }

    /**
     * @return bool
     */
    public function isDeferred(): bool
    {
        return $this->deferred;
    }

    /**
     * @return bool
     */
    public function hasChanges(): bool
    {
        return !empty($this->queue);
    }

    /**
     * @return $this
     */
    public function defer(): static
    {
        $this->deferred = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function commit(): static
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

    /**
     * @param callable $callback
     * @return void
     */
    public function afterCommit(callable $callback): void
    {
        $this->afterCommitCallbacks[] = $callback;
    }

    /**
     * @param $call
     * @param ...$args
     * @return void
     */
    protected function enqueue($call, ...$args): void
    {
        $this->queue[] = compact('call', 'args');
    }

    /**
     * @param array $task
     * @return mixed
     */
    protected function executeTask(array $task): mixed
    {
        return $this->actual->{$task['call']}(...$task['args']);
    }

    /**
     * @param $ttl
     * @return Carbon|null
     */
    protected function toAbsoluteTtl($ttl): ?Carbon
    {
        if (is_null($ttl)) return null;
        if (is_int($ttl)) return Carbon::createFromTimestamp(time() + $ttl);
        if ($ttl instanceof Carbon) return $ttl;
        if ($ttl instanceof DateTimeInterface) return Carbon::instance($ttl);
        throw new RuntimeException('Unknown type for TTL: '.Util::toString($ttl));
    }
}
