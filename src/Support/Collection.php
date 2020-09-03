<?php

namespace Exelion\Support;

use ArrayAccess;

class Collection extends Enumerable implements ArrayAccess
{
    use Concerns\Macroable;

    /**
     * @param iterable|Collection|null $items
     */
    public function __construct(iterable|Collection|null $items = null)
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
    public function newInstance($items): self
    {
        return new static($items);
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
     * @return float|int
     */
    public function avg()
    {
        return (float) $this->sum() / $this->count();
    }

    /**
     * @param int $size
     * @return $this
     */
    public function chunk(int $size)
    {
        $chunks = [];
        foreach (array_chunk($this->toArray(), $size, true) as $chunk) {
            $chunks[] = $this->newInstance($chunk);
        }
        return $this->newInstance($chunks);
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
     * @param int $amount
     * @return static
     */
    public function drop(int $amount)
    {
        return $this->slice(-$amount);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function dropUntil(callable $callback)
    {
        $index = $this->index($callback) ?? PHP_INT_MAX;
        return $this->drop($index);
    }

    /**
     * @param callable $callable
     * @return static
     */
    public function flatMap(callable $callable)
    {
        return $this->map($callable)->flatten();
    }

    /**
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth = PHP_INT_MAX)
    {
        $results = [];
        $func = function($values, int $depth) use (&$func, &$results) {
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
     * @param $value
     * @return int|null
     */
    public function index($value): ?int
    {
        if (!is_callable($value)) {
            $value = static fn($item, $index) => $item === $value;
        }
        foreach ($this->items as $index => $item) {
            if ($this->assureBool($value($item, $index), true)) {
                return $index;
            }
        }
        return null;
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
     * @param string $glue
     * @return string
     */
    public function implode(string $glue): string
    {
        return implode($glue, $this->items);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function keyBy(string $name)
    {
        $map = [];
        foreach ($this->items as $item) {
            $map[$name] = $item[$name];
        }
        return $this->newInstance($map);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[] = $callback($item, $key);
        }
        return $this->newInstance($values);
    }

    /**
     * @return int|float
     */
    public function max()
    {
        return max(...$this->items);
    }

    /**
     * @param iterable $collection
     * @return $this
     */
    public function merge(iterable $collection)
    {
        $this->items = array_merge($this->items, $this->asArray($collection));
        return $this;
    }

    /**
     * @return int|float
     */
    public function min()
    {
        return min(...$this->items);
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
            array_push($this->items, $v);
        }
        return $this;
    }

    /**
     * @return static
     */
    public function reverse()
    {
        return $this->newInstance(array_reverse($this->toArray(), false));
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

    public function removeAt(int ...$index)
    {
        foreach ($index as $i) {
            array_splice($this->items, $i, 1);
        }
        return $this;
    }

    public function rindex($value): ?int
    {
        $lastIndex = null;
        foreach ($this->items as $index => $item) {
            if ($item === $value) {
                $lastIndex = $index;
            }
        }
        return $lastIndex;
    }

    public function sample()
    {
        $index = rand(0, $this->count());
        return $this->items[$index];
    }

    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function skip(int $amount)
    {
        return $this->newInstance(array_slice($this->items, $this->count() - $amount, $amount));
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, int $length = null)
    {
        return $this->newInstance(array_slice($this->toArray(), $offset, $length));
    }

    public function shuffle()
    {
        $copy = $this->items;
        shuffle($copy);
        return $copy;
    }

    public function sortWith(callable $callback)
    {
        return parent::sortWith($callback)->reorder();
    }

    public function sum()
    {
        return array_sum(...$this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function take(int $amount)
    {
        return $this->slice(0, $amount);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function takeUntil(callable $callback)
    {
        $index = $this->index($callback) ?? PHP_INT_MAX;
        return $this->take($index);
    }

    /**
     * @return array
     */
    public function tally()
    {
        $mapping = [];
        foreach ($this->items as $item) {
            $mapping[$item] ??= 0;
            $mapping[$item] += 1;
        }
        return $mapping;
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
     * @return static
     */
    public function unique()
    {
        return $this->newInstance(array_unique($this->toArray()));
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
