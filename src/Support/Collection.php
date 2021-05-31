<?php

namespace Kirameki\Support;

use ArrayAccess;

/**
 * @template T
 */
class Collection extends Enumerable implements ArrayAccess
{
    /**
     * @param iterable|null $items
     */
    public function __construct(?iterable $items = null)
    {
        $this->items = $this->asArray($items ?? []);
    }

    /**
     * @param iterable|null $items
     * @return static
     */
    public function newInstance(?iterable $items = null): static
    {
        return new static($items);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            Assert::validKey($offset);
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * @param int $index
     * @param mixed $value
     * @return $this
     */
    public function insertAt(int $index, mixed $value): static
    {
        // Offset is off by one for negative indexes (Ex: -2 inserts at 3rd element from right).
        // So we add one to correct offset. If adding to one results in 0, we set it to max count
        // to put it at the end.
        if ($index < 0) {
            $index = $index === -1 ? $this->count() : $index + 1;
        }
        array_splice($this->items, $index, 0, $value);
        return $this;
    }

    /**
     * @param int $size
     * @param mixed $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->newInstance(array_pad($this->items, $size, $value));
    }

    /**
     * @return mixed
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * @param int|string $key
     * @return T
     */
    public function pull(mixed $key): mixed
    {
        if (static::isNotDottedKey($key)) {
            $value = $this->items[$key] ?? null;
            unset($this->items[$key]);
            return $value;
        }
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        if (is_array($array = static::digTo($this->items, $segments))) {
            $value = $array[$lastSegment];
            unset($array[$lastSegment]);
            return $value;
        }
        return null;
    }

    /**
     * @param T ...$value
     * @return $this
     */
    public function push(mixed ...$value): static
    {
        foreach ($value as $v) {
            $this->items[] = $v;
        }
        return $this;
    }

    /**
     * @param T $value
     * @param int|null $limit
     * @return $this
     */
    public function remove(mixed $value, ?int $limit = null): static
    {
        Arr::remove($this->items, $value, $limit);
        return $this;
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function removeKey(mixed $key): bool
    {
        Assert::validKey($key);

        $copy = $this->toArray();
        if (static::isNotDottedKey($key)) {
            if (array_key_exists($key, $copy)) {
                unset($copy[$key]);
                return true;
            }
            return false;
        }
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        if (is_array($array = static::digTo($copy, $segments))) {
            unset($array[$lastSegment]);
            return true;
        }
        return false;
    }

    /**
     * @return $this
     */
    public function reorder(): static
    {
        uasort($this->items, static fn () => 0);
        return $this;
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @return $this
     */
    public function set(mixed $key, mixed $value): static
    {
        Assert::validKey($key);

        if (static::isNotDottedKey($key)) {
            $this->items[$key] = $value;
            return $this;
        }

        $ptr = &$this->items;
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        foreach ($segments as $segment) {
            $ptr[$segment] ??= [];
            $ptr = &$ptr[$segment];
        }
        $ptr[$lastSegment] = $value;

        return $this;
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @return $this
     */
    public function setIfNotExists(mixed $key, mixed $value): static
    {
        if ($this->containsKey($key)) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @return T
     */
    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function transformKeys(callable $callback): static
    {
        return $this->newInstance(Arr::transformKeys($this->items, $callback));
    }

    /**
     * @param callable $callback
     * @param int $depth
     * @return $this
     */
    public function transformKeysRecursive(callable $callback, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::transformKeysRecursive($this->items, $callback, $depth));
    }

    /**
     * @param iterable $iterable
     * @return $this
     */
    public function unionKeys(iterable $iterable): static
    {
        return $this->newInstance(Arr::unionKeys($this->items, $iterable));
    }

    /**
     * @param iterable $iterable
     * @param int $depth
     * @return $this
     */
    public function unionKeysRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::unionKeysRecursive($this->items, $iterable, $depth));
    }

    /**
     * @param mixed ...$value
     * @return $this
     */
    public function unshift(mixed ...$value): static
    {
        foreach ($value as $v) {
            array_unshift($this->items, $v);
        }
        return $this;
    }
}
