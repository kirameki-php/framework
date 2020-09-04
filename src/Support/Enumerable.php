<?php

namespace Kirameki\Support;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use Traversable;

abstract class Enumerable implements Countable, IteratorAggregate, JsonSerializable
{
    use Concerns\Tappable;

    /**
     * @var iterable
     */
    protected iterable $items;

    /**
     * @param $values
     * @return static
     */
    abstract public function newInstance($values);

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $values = [];
        foreach ($this->items as $item) {
            $values[]= ($item instanceof JsonSerializable)
                ? $item->jsonSerialize()
                : $item;
        }
        return $values;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->asArray($this->items);
    }

    /**
     * @param $value
     * @return bool
     */
    public function contains($value): bool
    {
        if (!is_callable($value)) {
            $value = static fn($item) => $item === $value;
        }
        foreach ($this->items as $item) {
            $result = $value($item);
            if ($this->assureBool($result, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return static
     */
    public function compact()
    {
        return $this->filter();
    }

    /**
     * @param callable|null $callback
     * @return int
     */
    public function count(callable $callback = null): int
    {
        if ($callback === null) {
            return count($this->toArray());
        }
        $counter = 0;
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);
            if ($this->assureBool($result, true)) {
                $counter+= 1;
            }
        }
        return $counter;
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diff(iterable $items)
    {
        return $this->newInstance(array_diff($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param $items
     * @param callable $callback
     * @return static
     */
    public function diffWith($items, callable $callback)
    {
        return $this->newInstance((array_udiff($this->toArray(), $this->asArray($items), $callback)));
    }

    /**
     * @param callable $callback
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }
    }

    /**
     * @param callable $callback
     */
    public function eachWithIndex(callable $callback)
    {
        $offset = 0;
        foreach ($this->items as $key => $item) {
            $callback($item, $key, $offset);
            $offset+= 1;
        }
    }

    /**
     * @param iterable $items
     * @return bool
     */
    public function equals(iterable $items): bool
    {
        return $this->diff($items)->count() === 0;
    }

    /**
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback === null) {
            $callback = static fn($item, $key) => $item !== null;
        }
        $values = [];
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);
            if ($this->assureBool($result, true)) {
                $values[]= $result;
            }
        }
        return $this->newInstance($values);
    }

    /**
     * @param callable|null $callback
     * @return mixed|null
     */
    public function first(callable $callback = null)
    {
        if ($callback === null) {
            return current($this->toArray());
        }
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);
            if ($this->assureBool($result, true)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return static
     */
    public function groupBy(string $name)
    {
        $map = [];
        foreach ($this->items as $item) {
            $map[$name] ??= $this->newCollection();
            $map[$name][] = $item[$name];
        }
        return $this->newInstance($map);
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function intersect(iterable $items)
    {
        return $this->newInstance(array_intersect($this->toArray(), $this->asArray($items)));
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @param callable|null $callback
     * @return mixed|null
     */
    public function last(callable $callback = null)
    {
        $itemsArray = $this->toArray();
        if ($callback === null) {
            return end($itemsArray);
        }
        foreach (array_reverse($itemsArray) as $key => $item) {
            $result = $callback($item, $key);
            if ($this->assureBool($result, true)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param iterable $collection
     * @return $this
     */
    public function merge(iterable $collection)
    {
        $this->items = array_merge($this->toArray(), $this->asArray($collection));
        return $this;
    }

    /**
     * @param iterable|null $items
     * @return Collection
     */
    public function newCollection(iterable|null $items = null)
    {
        return new Collection($items);
    }

    /**
     * @param $value
     * @return bool
     */
    public function notContains($value): bool
    {
        return !$this->contains($value);
    }

    /**
     * @param callable $callback
     * @param null $initial
     * @return mixed|null
     */
    public function reduce(callable $callback, $initial = null)
    {
        $result = $initial;
        foreach ($this->items as $key => $item) {
            $result = $callback($result, $item, $key);
        }
        return $result;
    }

    /**
     * @param $value
     * @param int|null $limit
     * @return $this
     */
    public function remove($value, int|null $limit = null)
    {
        $counter = 0;
        foreach ($this->items as $key => $item) {
            if ($counter < $limit && $item === $value) {
                unset($this->items[$key]);
                $counter += 1;
            }
        }
        return $this;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function satisfyAll(callable $callback): bool
    {
        foreach ($this->items as $item) {
            $result = $callback($item);
            if ($this->assureBool($result, false)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function satisfyAny(callable $callback): bool
    {
        return $this->contains($callback);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sort(int $flag = SORT_REGULAR)
    {
        $copy = $this->toArray();
        asort($copy, $flag);
        return $this->newInstance($copy);
    }

    /**
     * @param callable $callback
     * @param int $flag
     * @return static
     */
    public function sortBy(callable $callback, int $flag = SORT_REGULAR)
    {
        return $this->sortByInternal($callback, $flag, true);
    }

    /**
     * @param callable $callback
     * @param int $flag
     * @return static
     */
    public function sortByDesc(callable $callback, int $flag = SORT_REGULAR)
    {
        return $this->sortByInternal($callback, $flag, false);
    }

    /**
     * @param callable $callback
     * @param int $flag
     * @param bool $ascending
     * @return static
     */
    protected function sortByInternal(callable $callback, int $flag, bool $ascending)
    {
        $refs = [];
        foreach ($this->items as $key => $item) {
            $refs[$key] = $callback($item, $key);
        }
        $ascending ? asort($refs, $flag) : arsort($refs, $flag);
        $sorted = [];
        foreach ($sorted as $key => $_) {
            $sorted[$key] = $this->items[$key];
        }
        return $this->newInstance($sorted);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function sortWith(callable $callback)
    {
        $copy = $this->toArray();
        uasort($copy, $callback);
        return $this->newInstance($copy);
    }

    /**
     * @param iterable $items
     * @return array
     */
    protected function asArray(iterable $items): array
    {
        if (is_array($items)) {
            return $items;
        }
        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        throw new RuntimeException('Unknown type:'.get_class($items));
    }

    /**
     * @param $value
     * @return int
     */
    protected function assureInteger($value)
    {
        if (!is_int($value)) {
            $message = sprintf("Invalid offset: %s. Integer expected.", Util::valueAsString($value));
            throw new RuntimeException($message);
        }
        return $value;
    }

    /**
     * @param $value
     * @param bool $expected
     * @return bool
     */
    protected function assureBool($value, bool $expected): bool
    {
        if (!is_bool($value)) {
            $result = Util::valueAsString($value);
            $message = "Invalid return value: $result. Call must return a boolean value";
            throw new RuntimeException($message);
        }
        return $value === $expected;
    }
}
