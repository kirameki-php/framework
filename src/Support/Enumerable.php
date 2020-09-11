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
     * @param bool $preserveKeys
     * @return static
     */
    public function chunk(int $size, bool $preserveKeys = true)
    {
        $chunks = [];
        foreach (array_chunk($this->toArray(), $size, $preserveKeys) as $chunk) {
            $chunks[] = $this->newInstance($chunk);
        }
        return $this->newInstance($chunks);
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
     * @param bool $asArray
     * @return $this
     */
    public function dd(bool $asArray = false)
    {
        dd($asArray ? $this->toArray() : $this);
        return $this;
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
     * @param iterable $items
     * @return static
     */
    public function diff(iterable $items)
    {
        return $this->newInstance(array_diff($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diffKeys(iterable $items)
    {
        return $this->newInstance(array_diff_key($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param int|string $key
     * @return static
     */
    public function dig($key)
    {
        $items = $this->toArray();
        $keys = is_string($key) && str_contains($key, '.') ? explode('.', $key) : [$key];
        return $this->newInstance($this->digTo($items, $keys));
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
     * @param bool $asArray
     * @return $this
     */
    public function dump(bool $asArray = false)
    {
        dump($asArray ? $this->toArray() : $this);
        return $this;
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
     * @param int $size
     * @param callable $callback
     */
    public function eachChunk(int $size, callable $callback)
    {
        $count = $size;
        $chunk = $this->newInstance();
        foreach ($this->items as $key => $item) {
            $chunk[$key] = $item;
            $count--;
            if ($count === 0) {
                $callback($chunk);
                $count = $size;
                $chunk = $this->newInstance();
            }
        }
        if ($chunk->isNotEmpty()) {
            $callback($chunk);
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
     * @param mixed|null $items
     * @return bool
     */
    public function equals($items): bool
    {
        return is_iterable($items) && $this->toArray() === $this->asArray($items);
    }

    /**
     * @param int|string ...$key
     * @return static
     */
    public function except(...$key)
    {
        $copy = $this->toArray();
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
        $items = $this->toArray();
        return is_string($key) && str_contains($key, '.')
            ? (bool) $this->digTo($items, explode('.', $key))
            : array_key_exists($key, $items);
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
            foreach ($this->items as $item) {
                return $item;
            }
            return null;
        }
        foreach ($this->items as $key => $item) {
            if ($this->assureTrue($callback($item, $key))) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @return int|string|null
     */
    public function firstKey()
    {
        foreach ($this->items as $key => $item) {
            return $key;
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
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth = PHP_INT_MAX)
    {
        $results = [];
        $func = static function($values, int $depth) use (&$func, &$results) {
            foreach ($values as $value) {
                if (!is_iterable($value) || $depth === 0) {
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
     * @param string $name
     * @return static
     */
    public function groupBy(string $name)
    {
        $map = [];
        foreach ($this->items as $item) {
            $map[$name] ??= new Collection();
            $map[$name][] = $item[$name];
        }
        return $this->newInstance($map);
    }

    /**
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public function implode(string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return $prefix.implode($glue, $this->toArray()).$suffix;
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
     * @param iterable $items
     * @return static
     */
    public function intersect(iterable $items)
    {
        return $this->newInstance(array_intersect($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function intersectKeys(iterable $items)
    {
        return $this->newInstance(array_intersect_key($this->toArray(), $this->asArray($items)));
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
     * @return static
     */
    public function keyBy(string $name)
    {
        $map = [];
        foreach ($this->items as &$item) {
            $key = $this->digTo($item, explode('.', $name));
            $map[$key] = $item;
        }
        return $this->newInstance($map);
    }

    /**
     * @return static
     */
    public function keys()
    {
        return $this->newInstance(array_keys($this->toArray()));
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
     * @return int|string|null
     */
    public function lastKey()
    {
        return array_key_last($this->toArray());
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
     * @param iterable $collection
     * @return static
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
     * @param mixed|null $items
     * @return bool
     */
    public function notEquals($items): bool
    {
        return ! $this->equals($items);
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
     * @return static
     */
    public function only(...$key)
    {
        $items = $this->toArray();
        $array = [];
        foreach ($key as $k) {
            $array[$k] = $items[$k];
        }
        return $this->newInstance($array);
    }

    /**
     * @param string $key
     * @return static
     */
    public function pluck(string $key)
    {
        return $this->newInstance($this->pluckInternal($key));
    }

    /**
     * @param $key
     * @param string|int|null $indexBy
     * @return array
     */
    protected function pluckInternal($key): array
    {
        if (is_string($key) && !str_contains($key, '.')) {
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
     * @param int|string $key
     * @return bool
     */
    public function removeKey($key): bool
    {
        if (is_string($key) && !str_contains($key, '.')) {
            if (array_key_exists($key, $this->toArray())) {
                unset($this->items[$key]);
                return true;
            }
            return false;
        }
        $items = $this->toArray();
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        if (is_array($array = $this->digTo($items, $segments))) {
            unset($array[$lastSegment]);
            return true;
        }
        return false;
    }

    /**
     * @param bool $preserveKeys
     * @return static
     */
    public function reverse(bool $preserveKeys = true)
    {
        return $this->newInstance(array_reverse($this->toArray(), $preserveKeys));
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
     * @param bool $preserveKeys
     * @return static
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = true)
    {
        $sliced = array_slice($this->toArray(), $offset, $length, $preserveKeys);
        return $this->newInstance($sliced);
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
     * @param int $flag
     * @return static
     */
    public function sortByKeys($flag = SORT_REGULAR)
    {
        $copy = $this->toArray();
        ksort($copy, $flag);
        return $this->newInstance($copy);
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
        $items = $this->toArray();
        foreach ($items as $key => $item) {
            $refs[$key] = $callback($item, $key);
        }
        $ascending ? asort($refs, $flag) : arsort($refs, $flag);
        $sorted = [];
        foreach ($refs as $key => $_) {
            $sorted[$key] = $items[$key];
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
     * @return static
     */
    public function tally()
    {
        $mapping = [];
        foreach ($this->items as $item) {
            $mapping[$item] ??= 0;
            $mapping[$item]++;
        }
        return $this->newInstance($mapping);
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
     * @return static
     */
    public function values()
    {
        return $this->newInstance(array_values($this->toArray()));
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
    protected function digTo(array &$array, array $keys)
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
