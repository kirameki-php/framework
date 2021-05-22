<?php

namespace Kirameki\Support;

use ArrayAccess;

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
     */
    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    /**
     * @param mixed $offset
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
     * @param int|string $key
     * @return mixed|null
     */
    public function get(int|string $key)
    {
        return static::isDottedKey($key)
            ? static::digTo($this->items, explode('.', $key))
            : $this->items[$key] ?? null;
    }

    /**
     * @param int $index
     * @param $value
     * @return $this
     */
    public function insertAt(int $index, $value): static
    {
        array_splice($this->items, $index, 0, $value);
        return $this;
    }

    /**
     * @param int $size
     * @param $value
     * @return static
     */
    public function pad(int $size, $value): static
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
     * @return mixed
     */
    public function pull(int|string $key): mixed
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
     * @param mixed ...$value
     * @return $this
     */
    public function push(...$value): static
    {
        foreach ($value as $v) {
            $this->items[] = $v;
        }
        return $this;
    }

    /**
     * @param $value
     * @param int|null $limit
     * @return $this
     */
    public function remove($value, ?int $limit = null): static
    {
        Arr::remove($this->items, $value, $limit);
        return $this;
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function removeKey(int|string $key): bool
    {
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
     * @param $value
     * @return $this
     */
    public function set(int|string $key, $value): static
    {
        if (static::isNotDottedKey($key)) {
            $this->items[$key] = $value;
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
     * @param $key
     * @param $value
     * @return $this
     */
    public function setIfNotExists($key, $value): static
    {
        if ($this->containsKey($key)) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @return mixed
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
        foreach ($this->items as $key => $item) {
            $this->items[$callback($key, $item)] = $item;
        }
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function transformValues(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            $this->items[$key] = $callback($item, $key);
        }
        return $this;
    }

    /**
     * @param mixed ...$value
     * @return $this
     */
    public function unshift(...$value): static
    {
        foreach ($value as $v) {
            array_unshift($this->items, $v);
        }
        return $this;
    }
}
