<?php

namespace Kirameki\Support;

use ArrayAccess;

class Map extends Enumerable implements ArrayAccess
{
    use Concerns\Macroable;

    /**
     * @param iterable|Map|null $items
     */
    public function __construct(iterable|Map|null $items = null)
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
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function add($key, $value)
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->items = [];
        return $this;
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
     * @return int|string|null
     */
    public function firstKey()
    {
        return array_key_first($this->items);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        return $this->items[$key] ?? null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->items);
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
     * @param int|string ...$key
     * @return $this
     */
    public function only(int|string ...$key)
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
