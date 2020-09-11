<?php

namespace Kirameki\Support;

use ArrayAccess;

class Collection extends Enumerable implements ArrayAccess
{
    /**
     * @param iterable|Collection|null $items
     */
    public function __construct($items = null)
    {
        $this->items = $this->asArray($items ?? []);
    }

    /**
     * @param iterable|null $items
     * @return static
     */
    protected function newCollection(?iterable $items = null)
    {
        return $this->newInstance($items);
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
     * @return bool
     */
    protected function preserveKeys(): bool
    {
        return false;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        $this->assureInteger($offset);
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->assureInteger($offset);
        return $this->items[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->assureInteger($offset);
        $this->items[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->assureInteger($offset);
        unset($this->items[$offset]);
    }

    /**
     * @param int $index
     * @return mixed|null
     */
    public function at(int $index)
    {
        return $this->items[$index] ?? null;
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diff(iterable $items)
    {
        return $this->newInstance(array_diff($this->items, $this->asArray($items)));
    }

    /**
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth = PHP_INT_MAX)
    {
        $results = [];
        $func = static function($values, int $depth) use (&$func, &$results) {
            foreach ($values as $value) {
                if (!is_array($value) || $depth === 0) {
                    $results[] = $value;
                } else {
                    $func($value, $depth - 1);
                }
            }
        };
        $func($this->items, $depth);
        return $this->newInstance($results);
    }

    /**
     * @return Collection
     */
    public function indexes()
    {
        return $this->newInstance(array_keys($this->items));
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
     * @param iterable $items
     * @return static
     */
    public function intersect(iterable $items)
    {
        return $this->newInstance(array_intersect($this->items, $this->asArray($items)));
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
     * @param int $index
     * @return mixed
     */
    public function pull(int $index)
    {
        $value = $this->items[$index];
        unset($this->items[$index]);
        return $value;
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
        return parent::remove($value, $limit)->reorder();
    }

    /**
     * @param int ...$index
     * @return $this
     */
    public function removeAt(int ...$index)
    {
        foreach ($index as $i) {
            array_splice($this->items, $i, 1);
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
     * @return static
     */
    public function sortWith(callable $callback)
    {
        return parent::sortWith($callback)->reorder();
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function transform(callable $callback)
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

    /**
     * @param int ...$index
     * @return static
     */
    public function valuesAt(int ...$index)
    {
        $values = [];
        foreach ($index as $i) {
            $values[]= $this->items[$i];
        }
        return $this->newInstance($values);
    }

    /**
     * @return $this
     */
    protected function reorder()
    {
        usort($this->items, static fn() => 0);
        return $this;
    }
}
