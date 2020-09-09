<?php

namespace Kirameki\Support;

use ArrayAccess;

class Map extends Enumerable implements ArrayAccess
{
    use Concerns\Macroable;

    /**
     * @param iterable|null $items
     */
    public function __construct(?iterable $items = null)
    {
        if ($items === null) {
            $items = [];
        }
        $this->items = $this->asArray($items);
    }

    /**
     * @param $items
     * @return static
     */
    public function newInstance($items)
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
    public function offsetGet($offset)
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
    public function clear()
    {
        $this->items = [];
        return $this;
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diff(iterable $items)
    {
        return $this->newInstance(array_diff_assoc($this->items, $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diffKeys(iterable $items)
    {
        return $this->newInstance(array_diff_key($this->items, $this->asArray($items)));
    }


    public function except(int|string ...$key)
    {
        $copy = $this->items;
        foreach ($key as $k) {
            unset($copy[$k]);
        }
        return $this->newInstance($copy);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function exists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @return int|string|null
     */
    public function firstKey()
    {
        return array_key_first($this->items);
    }

    /**
     * @param int|string $key
     * @return mixed|null
     */
    public function get($key)
    {
        return $this->items[$key] ?? null;
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function intersect(iterable $items)
    {
        return $this->newInstance(array_intersect_assoc($this->items, $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function intersectKeys(iterable $items)
    {
        return $this->newInstance(array_intersect_key($this->items, $this->asArray($items)));
    }

    /**
     * @return Collection
     */
    public function keys()
    {
        return $this->newCollection(array_keys($this->items));
    }

    /**
     * @return int|string|null
     */
    public function lastKey()
    {
        return array_key_last($this->items);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function notExists($key): bool
    {
        return !$this->exists($key);
    }

    /**
     * @param int|string ...$key
     * @return $this
     */
    public function only(...$key)
    {
        $map = [];
        foreach ($key as $k) {
            $map[$k] = $this->items[$k];
        }
        return $this->newInstance($map);
    }

    /**
     * @return static
     */
    public function reverse()
    {
        return $this->newInstance(array_reverse($this->toArray(), true));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function pull($key)
    {
        $value = $this->items[$key];
        unset($this->items[$key]);
        return $value;
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKeys($flag = SORT_REGULAR)
    {
        $copy = $this->items;
        ksort($copy, $flag);
        return $this->newInstance($copy);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setIfNotExists($key, $value)
    {
        if ($this->notExists($key)) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function transformKeys(callable $callback)
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
    public function transformValues(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            $this->items[$key] = $callback($item, $key);
        }
        return $this;
    }

    /**
     * @return Collection
     */
    public function values()
    {
        return $this->newCollection(array_values($this->items));
    }
}
