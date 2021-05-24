<?php

namespace Kirameki\Support;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use Kirameki\Exception\UnexpectedArgumentException;

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
    abstract public function newInstance(?iterable $items = null): static;

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[$key] = ($item instanceof JsonSerializable) ? $item->jsonSerialize() : $item;
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
     * @return float|int
     */
    public function average(): float|int
    {
        return (float)$this->sum() / $this->count();
    }

    /**
     * @param int $size
     * @param bool $preserveKeys
     * @return static
     */
    public function chunk(int $size, bool $preserveKeys = true): static
    {
        $chunks = [];
        foreach (array_chunk($this->toArray(), $size, $preserveKeys) as $chunk) {
            $chunks[] = $this->newInstance($chunk);
        }
        return $this->newInstance($chunks);
    }

    /**
     * @param int $depth
     * @return $this
     */
    public function compact(int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::compact($this->items, $depth));
    }

    /**
     * @param $value
     * @return bool
     */
    public function contains($value): bool
    {
        return Arr::contains($this->items, $value);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function containsKey(int|string $key): bool
    {
        $copy = $this->toArray();
        if (static::isNotDottedKey($key)) {
            return array_key_exists($key, $copy);
        }
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $ptr = static::digTo($copy, $segments);
        return is_array($ptr) && array_key_exists($lastSegment, $ptr);
    }

    /**
     * @return static
     */
    public function copy(): static
    {
        return $this->newInstance($this->items);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->toArray());
    }

    /**
     * @param callable $condition
     * @return int
     */
    public function countBy(callable $condition): int
    {
        return Arr::countBy($this->items, $condition);
    }

    /**
     * @return Generator
     */
    public function cursor(): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * @param bool $asArray
     * @return $this
     */
    public function dd(bool $asArray = false): static
    {
        dd($asArray ? $this->toArray() : $this);
        return $this;
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function deepMerge(iterable $items): static
    {
        return $this->newInstance(array_merge_recursive($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diff(iterable $items): static
    {
        return $this->newInstance(array_diff($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function diffKeys(iterable $items): static
    {
        return $this->newInstance(array_diff_key($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param int|string $key
     * @return static|null
     */
    public function dig(int|string $key): static|null
    {
        $copy = $this->toArray();
        $keys = static::isDottedKey($key) ? explode('.', $key) : [$key];
        $dug = static::digTo($copy, $keys);
        return is_iterable($dug) ? $this->newInstance($dug) : null;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function drop(int $amount): static
    {
        if ($amount < 0) {
            throw new UnexpectedArgumentException(0, 'positive value', $amount);
        }
        return $this->slice($amount);
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function dropUntil(callable $condition): static
    {
        $index = $this->firstIndex($condition) ?? PHP_INT_MAX;
        return $this->drop($index);
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function dropWhile(callable $condition): static
    {
        $index = $this->firstIndex(static function ($item, $key) use ($condition) {
                $result = $condition($item, $key);
                Assert::bool($result);
                return !$result;
            }) ?? PHP_INT_MAX;
        return $this->drop($index);
    }

    /**
     * @param bool $asArray
     * @return $this
     */
    public function dump(bool $asArray = false): static
    {
        dump($asArray ? $this->toArray() : $this);
        return $this;
    }

    /**
     * @param callable $callback
     */
    public function each(callable $callback): void
    {
        Arr::each($this->items, $callback);
    }

    /**
     * @param int $size
     * @param callable $callback
     */
    public function eachChunk(int $size, callable $callback): void
    {
        Arr::eachChunk($this->items, $size, function(array $items, int $count) use ($callback) {
            $callback($this->newInstance($items), $count);
        });
    }

    /**
     * @param callable $callback
     */
    public function eachWithIndex(callable $callback): void
    {
        Arr::eachWithIndex($this->items, $callback);
    }

    /**
     * @param mixed|null $items
     * @return bool
     */
    public function equals(iterable $items): bool
    {
        return is_iterable($items) && $this->toArray() === $this->asArray($items);
    }

    /**
     * @param int|string ...$key
     * @return static
     */
    public function except(...$key): static
    {
        $copy = $this->toArray();
        foreach ($key as $k) {
            unset($copy[$k]);
        }
        return $this->newInstance($copy);
    }

    /**
     * @param callable|null $condition
     * @return static
     */
    public function filter(callable $condition = null): static
    {
        return $this->newInstance(Arr::filter($this->items, $condition));
    }

    /**
     * @param callable|null $condition
     * @return mixed
     */
    public function first(callable $condition = null): mixed
    {
        return Arr::first($this->items, $condition);
    }

    /**
     * @param callable $condition
     * @return int|null
     */
    public function firstIndex(callable $condition): ?int
    {
        return Arr::firstIndex($this->items, $condition);
    }

    /**
     * @param callable|null $condition
     * @return int|string|null
     */
    public function firstKey(callable $condition = null): int|string|null
    {
        return Arr::firstKey($this->items, $condition);
    }

    /**
     * @param callable $callable
     * @return static
     */
    public function flatMap(callable $callable): static
    {
        return $this->newInstance(Arr::flatMap($this->items, $callable));
    }

    /**
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth = 1): static
    {
        Assert::positiveInt($depth);
        return $this->newInstance(Arr::flatten($this->items, $depth));
    }

    /**
     * @return $this
     */
    public function flip(): static
    {
        return $this->newInstance(array_flip($this->toArray()));
    }

    /**
     * @param string $name
     * @return static
     */
    public function groupBy(string $name): static
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
        return Arr::implode($this->items, $glue, $prefix, $suffix);
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->newInstance(array_intersect($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable $items
     * @return static
     */
    public function intersectKeys(iterable $items): static
    {
        return $this->newInstance(array_intersect_key($this->toArray(), $this->asArray($items)));
    }

    /**
     * @return bool
     */
    public function isAssoc(): bool
    {
        return Arr::isAssoc($this->items);
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
     * @return bool
     */
    public function isSequential(): bool
    {
        return Arr::isSequential($this->items);
    }

    /**
     * @param string|callable $key
     * @return static
     */
    public function keyBy(callable|string $key): static
    {
        if (is_string($key)) {
            $segments = explode('.', $key);
            $call = static fn($v, $k) => static::digTo($v, $segments);
        } else {
            $call = $key;
        }
        $map = [];
        foreach ($this->items as $k => $item) {
            if (is_array($item)) {
                $newKey = $call($item, $k);
                if (is_string($newKey) || is_int($newKey)) {
                    $map[$newKey] = $item;
                }
            }
        }
        return $this->newInstance($map);
    }

    /**
     * @return static
     */
    public function keys(): static
    {
        return $this->newCollection(array_keys($this->toArray()));
    }

    /**
     * @param callable|null $condition
     * @return mixed
     */
    public function last(callable $condition = null): mixed
    {
        return Arr::last($this->items, $condition);
    }

    /**
     * @param callable $condition
     * @return int|null
     */
    public function lastIndex(callable $condition): ?int
    {
        return Arr::lastIndex($this->items, $condition);
    }

    /**
     * @param callable|null $condition
     * @return int|string|null
     */
    public function lastKey(callable $condition = null): int|string|null
    {
        return Arr::lastKey($this->items, $condition);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        return $this->newInstance(Arr::map($this->items, $callback));
    }

    /**
     * @param iterable $collection
     * @return static
     */
    public function merge(iterable $collection): static
    {
        return $this->newInstance(array_merge($this->toArray(), $this->asArray($collection)));
    }

    /**
     * @return int|float
     */
    public function max(): int|float
    {
        return max(...$this->toArray());
    }

    /**
     * @return int|float
     */
    public function min(): int|float
    {
        return min(...$this->toArray());
    }

    /**
     * @return array
     */
    public function minMax(): array
    {
        $min = null;
        $max = null;
        foreach ($this->items as $value) {
            if ($min === null || $min < $value) {
                $min = $value;
            }
            if ($max === null || $max > $value) {
                $max = $value;
            }
        }
        return [$min, $max];
    }

    /**
     * @param int|string $value
     * @return bool
     */
    public function notContains(int|string $value): bool
    {
        return Arr::notContains($this->items, $value);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function notContainsKey(int|string $key): bool
    {
        return !$this->containsKey($key);
    }

    /**
     * @param mixed|null $items
     * @return bool
     */
    public function notEquals($items): bool
    {
        return !$this->equals($items);
    }

    /**
     * @param int|string ...$key
     * @return static
     */
    public function only(...$key): static
    {
        $copy = $this->toArray();
        $array = [];
        foreach ($key as $k) {
            $array[$k] = $copy[$k];
        }
        return $this->newInstance($array);
    }

    /**
     * @param string $key
     * @return static
     */
    public function pluck(string $key): static
    {
        if (static::isNotDottedKey($key)) {
            return $this->newCollection(array_column($this->toArray(), $key));
        }
        $plucked = [];
        $segments = explode('.', $key);
        foreach ($this->items as $values) {
            $plucked[] = static::digTo($values, $segments);
        }
        return $this->newCollection($plucked);
    }

    /**
     * @param callable $callback
     * @param null $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null): mixed
    {
        $result = $initial ?? $this->newInstance();
        foreach ($this->items as $key => $item) {
            $result = $callback($result, $item, $key);
        }
        return $result;
    }

    /**
     * @param bool $preserveKeys
     * @return static
     */
    public function reverse(bool $preserveKeys = true): static
    {
        return $this->newInstance(array_reverse($this->toArray(), $preserveKeys));
    }

    /**
     * @return mixed
     */
    public function sample(): mixed
    {
        return Arr::sample($this->items);
    }

    /**
     * @param callable $condition
     * @return bool
     */
    public function satisfyAll(callable $condition): bool
    {
        return Arr::satisfyAll($this->items, $condition);
    }

    /**
     * @param callable $condition
     * @return bool
     */
    public function satisfyAny(callable $condition): bool
    {
        return Arr::satisfyAny($this->items, $condition);
    }

    /**
     * @return $this
     */
    public function shuffle(): static
    {
        return $this->newInstance(Arr::shuffle($this->items));
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @param bool $preserveKeys
     * @return static
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = true): static
    {
        $sliced = array_slice($this->toArray(), $offset, $length, $preserveKeys);
        return $this->newInstance($sliced);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sort(int $flag = SORT_REGULAR): static
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
    public function sortBy(callable $callback, int $flag = SORT_REGULAR): static
    {
        return $this->sortByInternal($callback, $flag, true);
    }

    /**
     * @param callable $callback
     * @param int $flag
     * @return static
     */
    public function sortByDesc(callable $callback, int $flag = SORT_REGULAR): static
    {
        return $this->sortByInternal($callback, $flag, false);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKeys(int $flag = SORT_REGULAR): static
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
    protected function sortByInternal(callable $callback, int $flag, bool $ascending): static
    {
        $refs = [];
        $copy = $this->toArray();
        foreach ($copy as $key => $item) {
            $refs[$key] = $callback($item, $key);
        }
        $ascending ? asort($refs, $flag) : arsort($refs, $flag);
        $sorted = [];
        foreach ($refs as $key => $_) {
            $sorted[$key] = $copy[$key];
        }
        return $this->newInstance($sorted);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function sortWith(callable $callback): static
    {
        $copy = $this->toArray();
        uasort($copy, $callback);
        return $this->newInstance($copy);
    }

    /**
     * @return float|int
     */
    public function sum(): float|int
    {
        return array_sum($this->toArray());
    }

    /**
     * @param int $amount
     * @return static
     */
    public function take(int $amount): static
    {
        if ($amount < 0) {
            throw new UnexpectedArgumentException(0, 'positive value', $amount);
        }
        return $this->slice(0, $amount);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function takeUntil(callable $callback): static
    {
        $index = $this->firstIndex($callback) ?? PHP_INT_MAX;
        return $this->take($index);
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function takeWhile(callable $condition): static
    {
        $index = $this->firstIndex(static fn($item, $key) => !$condition($item, $key)) ?? PHP_INT_MAX;
        return $this->take($index);
    }

    /**
     * @return static
     */
    public function tally(): static
    {
        $mapping = [];
        foreach ($this->items as $item) {
            $mapping[$item] ??= 0;
            $mapping[$item]++;
        }
        return $this->newCollection($mapping);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->asArray($this->items);
    }

    /**
     * @return array
     */
    public function toArrayRecursive(): array
    {
        return $this->asArrayRecursive($this->items);
    }

    /**
     * @param int $options
     * @param int $depth
     * @return string
     */
    public function toJson(int $options = 0, int $depth = 512): string
    {
        return Json::encode($this->jsonSerialize(), $options, $depth);
    }

    /**
     * @param string|null $namespace
     * @return string
     */
    public function toUrlQuery(?string $namespace = null): string
    {
        return Arr::toUrlQuery($this->items, $namespace);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function unique(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(array_unique($this->toArray(), $flag));
    }

    /**
     * @return static
     */
    public function values(): static
    {
        return $this->newInstance(array_values($this->toArray()));
    }

    /**
     * @param iterable $items
     * @return array
     */
    protected function asArray(iterable $items): array
    {
        return Arr::from($items);
    }

    /**
     * @param iterable $items
     * @return array
     */
    protected function asArrayRecursive(iterable $items): array
    {
        return Arr::map(Arr::from($items), function ($item) {
            return is_iterable($item) ? $this->asArrayRecursive($item) : $item;
        });
    }

    /**
     * @param iterable $items
     * @return Collection
     */
    protected function newCollection(iterable $items): Collection
    {
        return new Collection($items);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    protected static function isDottedKey(int|string $key): bool
    {
        return is_string($key) && str_contains($key, '.');
    }

    /**
     * @param int|string $key
     * @return bool
     */
    protected static function isNotDottedKey(int|string $key): bool
    {
        return !static::isDottedKey($key);
    }

    /**
     * @param array $array
     * @param array $keys
     * @return mixed
     */
    protected static function digTo(array $array, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return null;
            }
            if (!is_array($array[$key])) {
                // If at last key, return the referenced value
                if ($key === $keys[array_key_last($keys)]) {
                    return $array[$key];
                }
                return null;
            }
            $array = $array[$key];
        }
        return $array;
    }
}
