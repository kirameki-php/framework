<?php

namespace Kirameki\Support;

use ArrayAccess;

class Collection extends Enumerable implements ArrayAccess
{
    /**
     * @param iterable|null $items
     */
    public function __construct($items = null)
    {
        $this->items = $this->asArray($items ?? []);
    }

    /**
     * @param $items
     * @return static
     */
    public function newInstance(?iterable $items = null)
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
     * @param int|string $key
     * @return mixed|null
     */
    public function get($key)
    {
        return str_contains($key, '.')
            ? $this->digTo($this->items, explode('.', $key))
            : $this->items[$key] ?? null;
    }

    /**
     * @param int $index
     * @param $value
     * @return $this
     */
    public function insertAt(int $index, $value)
    {
        array_splice($this->items, $index, 0, $value);
        return $this;
    }

    /**
     * @param int $size
     * @param $value
     * @return static
     */
    public function pad(int $size, $value)
    {
        return $this->newInstance(array_pad($this->items, $size, $value));
    }

    /**
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * @param int|string $key
     * @return mixed|null
     */
    public function pull($key)
    {
        if (!str_contains($key, '.')) {
            $value = $this->items[$key] ?? null;
            unset($this->items[$key]);
            return $value;
        }
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        if (is_array($array = $this->digTo($this->items, $segments))) {
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
    public function push(...$value)
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
    public function remove($value, ?int $limit = null)
    {
        $counter = 0;
        foreach ($this->items as $key => $item) {
            if ($counter < $limit && $item === $value) {
                unset($this->items[$key]);
                $counter++;
            }
        }
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        if (!str_contains($key, '.')) {
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
    public function setIfNotExists($key, $value)
    {
        if ($this->notExists($key)) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
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
     * @param mixed ...$value
     * @return $this
     */
    public function unshift(...$value)
    {
        foreach ($value as $v) {
            array_unshift($this->items, $v);
        }
        return $this;
    }
}
