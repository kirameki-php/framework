<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends Enumerable<TKey, TValue>
 */
class Map extends Enumerable
{
    protected bool $isList = false;

    /**
     * @param int|string $key
     * @return bool
     */
    public function containsKey(int|string $key): bool
    {
        return Arr::containsKey($this, $key);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diffKeys(iterable $items): static
    {
        return $this->newInstance(Arr::diffKeys($this, $items));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public function firstKey(?Closure $condition = null): int|string|null
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersectKeys(iterable $items): static
    {
        return $this->newInstance(Arr::intersectKeys($this, $items));
    }

    /**
     * @return static<TKey>
     */
    public function keys(): static
    {
        return $this->newInstance(Arr::keys($this));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return mixed
     */
    public function lastKey(?Closure $condition = null): mixed
    {
        return Arr::lastKey($this, $condition);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function notContainsKey(int|string $key): bool
    {
        return Arr::notContainsKey($this, $key);
    }

    /**
     * @param string|null $namespace
     * @return string
     */
    public function toUrlQuery(?string $namespace = null): string
    {
        return Arr::toUrlQuery($this, $namespace);
    }

    /**
     * @return Vec<TValue>
     */
    public function values(): Vec
    {
        return new Vec(Arr::values($this));
    }
}
