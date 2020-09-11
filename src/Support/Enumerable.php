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
    use Concerns\Macroable;
    use Concerns\Tappable;

    /**
     * @var iterable
     */
    protected iterable $items;

    /**
     * @param iterable|null $items
     * @return static
     */
    abstract public function newInstance(?iterable $items = null);

    /**
     * @param string $function
     * @return bool
     */
    abstract protected function preserveKeys(): bool;

    /**
     * @param iterable|null $items
     * @return Collection
     */
    protected function newCollection(?iterable $items = null)
    {
        return new Collection($items);
    }

    /**
     * @param iterable|null $entries
     * @return Map
     */
    protected function newMap(?iterable $entries = [])
    {
        return new Map($entries);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[$key]= ($item instanceof JsonSerializable)
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
        foreach (array_chunk($this->toArray(), $size, $this->preserveKeys()) as $chunk) {
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
     * @return static
     */
    public function compact()
    {
        return $this->filter(static fn($item) => $item !== null);
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
            if ($this->assureTrue($result)) {
                return true;
            }
        }
        return false;
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
            if ($this->assureTrue($result)) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function deepMerge(iterable $items)
    {
        return $this->newInstance(array_merge_recursive($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param int|string $key
     * @return static
     */
    public function dig($key)
    {
        $keys = is_string($key) && str_contains($key, '.') ? explode('.', $key) : [$key];
        return $this->newInstance($this->digTo($this->items, $keys));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function drop(int $amount)
    {
        return $this->slice($amount);
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
            $offset++;
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
            $callback = static fn($item, $key) => !empty($item);
        }
        $values = [];
        foreach ($this->items as $key => $item) {
            $result = $callback($item, $key);
            if ($this->assureTrue($result)) {
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
            if ($this->assureTrue($callback($item, $key))) {
                return $item;
            }
        }
        return null;
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
     * @param string $glue
     * @return string
     */
    public function implode(string $glue): string
    {
        return implode($glue, $this->toArray());
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
            if ($this->assureTrue($value($item, $index))) {
                return $index;
            }
        }
        return null;
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
     * @param string $name
     * @return Map
     */
    public function keyBy(string $name)
    {
        $map = [];
        foreach ($this->items as &$item) {
            $key = $this->digTo($item, explode('.', $name));
            $map[$key] = $item;
        }
        return $this->newMap($map);
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
            if ($this->assureTrue($callback($item, $key))) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function map(callable $callback)
    {
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[] = $callback($item, $key);
        }
        return $this->newCollection($values);
    }

    /**
     * @param iterable $collection
     * @return $this
     */
    public function merge(iterable $collection)
    {
        return $this->newInstance(array_merge($this->toArray(), $this->asArray($collection)));
    }

    /**
     * @return int|float
     */
    public function max()
    {
        return max(...$this->items);
    }

    /**
     * @return int|float
     */
    public function min()
    {
        return min(...$this->items);
    }

    /**
     * @param int|string $value
     * @return bool
     */
    public function notContains($value): bool
    {
        return !$this->contains($value);
    }

    /**
     * @param string $key
     * @return Collection
     */
    public function pluck(string $key)
    {
        return $this->newCollection($this->pluckInternal($key));
    }

    /**
     * @param $key
     * @param string|int|null $indexBy
     * @return array
     */
    protected function pluckInternal($key): array
    {
        if (!str_contains($key, '.')) {
            return array_column($this->toArray(), $key);
        }
        $plucked = [];
        $keySegments = explode('.', $key);
        $lastKeySegment = array_pop($keySegments);
        foreach ($this->items as &$values) {
            $ptr = $this->digTo($values, $keySegments);
            if (is_array($ptr) && array_key_exists($lastKeySegment, $ptr)) {
                $plucked[] = $ptr[$lastKeySegment];
            }
        }
        return $plucked;
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
     * @return static
     */
    public function reverse()
    {
        return $this->newInstance(array_reverse($this->toArray(), $this->preserveKeys()));
    }

    /**
     * @param $value
     * @return int|null
     */
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

    /**
     * @return mixed
     */
    public function sample()
    {
        return $this->items[array_rand($this->toArray())];
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function satisfyAll(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if ($this->assureFalse($callback($item))) {
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
     * @return $this
     */
    public function shuffle()
    {
        $copy = $this->toArray();
        shuffle($copy);
        return $this->newInstance($copy);
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, int $length = null)
    {
        $sliced = array_slice($this->toArray(), $offset, $length, $this->preserveKeys());
        return $this->newCollection($sliced);
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
     * @return float|int
     */
    public function sum()
    {
        return array_sum(...$this->toArray());
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
     * @return Map
     */
    public function tally(): Map
    {
        $mapping = [];
        foreach ($this->items as $item) {
            $mapping[$item] ??= 0;
            $mapping[$item]++;
        }
        return $this->newMap($mapping);
    }

    /**
     * @param string|null $namespace
     * @return string
     */
    public function toUrlQuery(?string $namespace = null): string
    {
        $data = $namespace !== null
            ? [$namespace => $this->toArray()]
            : $this->toArray();
        return http_build_query($data, '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function unique(int $flag = SORT_REGULAR)
    {
        return $this->newInstance(array_unique($this->toArray(), $flag));
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
    protected function assureInteger($value): int
    {
        if (!is_int($value)) {
            $message = sprintf("Invalid offset: %s. Integer expected.", Util::valueAsString($value));
            throw new RuntimeException($message);
        }
        return $value;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function assureTrue($value): bool
    {
        return $this->assureBool($value, true);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function assureFalse($value): bool
    {
        return $this->assureBool($value, false);
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

    /**
     * @param $array
     * @param array $keys
     * @return mixed
     */
    protected function digTo(&$array, array $keys)
    {
        foreach ($keys as $key) {
            if (!isset($array[$key]) && !array_key_exists($key, $array)) {
                return null;
            }
            $array = $array[$key];
        }
        return $array;
    }
}
