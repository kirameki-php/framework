<?php declare(strict_types=1);

namespace Kirameki\Support;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use function array_chunk;
use function array_diff;
use function array_diff_key;
use function array_unique;
use function array_values;
use function count;
use function dd;
use function dump;
use function is_iterable;

/**
 * @template T
 */
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
     * @param bool|null $allowEmpty
     * @return float|int
     */
    public function average(?bool $allowEmpty = true): float|int
    {
        return Arr::average($this->items, $allowEmpty);
    }

    /**
     * @param int $size
     * @return static
     */
    public function chunk(int $size): static
    {
        $array = $this->toArray();
        $chunks = [];
        foreach (array_chunk($array, $size, Arr::isAssoc($array)) as $chunk) {
            $chunks[] = $this->newInstance($chunk);
        }
        return $this->newInstance($chunks);
    }

    /**
     * @param int $depth
     * @return $this
     */
    public function compact(int $depth = 1): static
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
        return Arr::containsKey($this->toArray(), $key);
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
     * @param int $amount
     * @return static
     */
    public function drop(int $amount): static
    {
        return $this->newInstance(Arr::drop($this->items, $amount));
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function dropUntil(callable $condition): static
    {
        return $this->newInstance(Arr::dropUntil($this->items, $condition));
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function dropWhile(callable $condition): static
    {
        return $this->newInstance(Arr::dropWhile($this->items, $condition));
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
     * @return void
     */
    public function each(callable $callback): void
    {
        Arr::each($this->items, $callback);
    }

    /**
     * @param int $size
     * @param callable $callback
     * @return void
     */
    public function eachChunk(int $size, callable $callback): void
    {
        Arr::eachChunk($this->items, $size, function(array $items, int $count) use ($callback) {
            $callback($this->newInstance($items), $count);
        });
    }

    /**
     * @param callable $callback
     * @return void
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
     * @param int[]|string[] $keys
     * @return static
     */
    public function except(iterable $keys): static
    {
        return $this->newInstance(Arr::except($this->items, $keys));
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
     * @return T
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
        return $this->newInstance(Arr::flatten($this->items, $depth));
    }

    /**
     * @param bool $overwrite
     * @return $this
     */
    public function flip(bool $overwrite = false): static
    {
        return $this->newInstance(Arr::flip($this->items, $overwrite));
    }

    /**
     * @param int|string $key
     * @return mixed
     */
    public function get(int|string $key): mixed
    {
        return Arr::get($this->items, $key);
    }

    /**
     * @param string|callable $key
     * @return static
     */
    public function groupBy(string|callable $key): static
    {
        return $this->newInstance(Arr::groupBy($this->items, $key))->map(fn($array) => $this->newCollection($array));
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
        return Arr::isEmpty($this->items);
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return Arr::isList($this->items);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return Arr::isNotEmpty($this->items);
    }

    /**
     * @param string|callable $key
     * @param bool $overwrite
     * @return $this
     */
    public function keyBy(string|callable $key, bool $overwrite = false): static
    {
        return $this->newInstance(Arr::keyBy($this->items, $key, $overwrite));
    }

    /**
     * @return Collection
     */
    public function keys(): Collection
    {
        return $this->newCollection(array_keys($this->toArray()));
    }

    /**
     * @param callable|null $condition
     * @return T
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
     * @return mixed
     */
    public function max(): mixed
    {
        return Arr::max($this->items);
    }

    /**
     * @param iterable $iterable
     * @return static
     */
    public function merge(iterable $iterable): static
    {
        return $this->newInstance(Arr::merge($this->items, $iterable));
    }

    /**
     * @param iterable $iterable
     * @param int $depth
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::mergeRecursive($this->items, $iterable, $depth));
    }

    /**
     * @return mixed
     */
    public function min(): mixed
    {
        return Arr::min($this->items);
    }

    /**
     * @return array
     */
    public function minMax(): array
    {
        return Arr::minMax($this->items);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function notContains(mixed $value): bool
    {
        return Arr::notContains($this->items, $value);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function notContainsKey(int|string $key): bool
    {
        return Arr::notContainsKey($this->items, $key);
    }

    /**
     * @param mixed $items
     * @return bool
     */
    public function notEquals(mixed $items): bool
    {
        return !$this->equals($items);
    }

    /**
     * @param iterable $keys
     * @return static
     */
    public function only(iterable $keys): static
    {
        return $this->newInstance(Arr::only($this->items, $keys));
    }

    /**
     * @param int|string $key
     * @return Collection
     */
    public function pluck(int|string $key): Collection
    {
        return $this->newCollection(Arr::pluck($this->items, $key));
    }

    /**
     * @template T_INIT
     * @param callable $callback
     * @param T_INIT|null $initial
     * @return T_INIT
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return Arr::reduce($this->items, $callback, $initial);
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->newInstance(Arr::reverse($this->items));
    }

    /**
     * @return T
     */
    public function sample(): mixed
    {
        return Arr::sample($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function sampleMany(int $amount): static
    {
        return $this->newInstance(Arr::sampleMany($this->items, $amount));
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
     * @return static
     */
    public function slice(int $offset, int $length = null): static
    {
        $array = $this->toArray();
        $sliced = array_slice($array, $offset, $length, Arr::isAssoc($array));
        return $this->newInstance($sliced);
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
     * @param int $flag
     * @return static
     */
    public function sortKeys(int $flag = SORT_REGULAR): static
    {
        $copy = $this->toArray();
        ksort($copy, $flag);
        return $this->newInstance($copy);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortKeysDesc(int $flag = SORT_REGULAR): static
    {
        $copy = $this->toArray();
        krsort($copy, $flag);
        return $this->newInstance($copy);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortValues(int $flag = SORT_REGULAR): static
    {
        $copy = $this->toArray();
        asort($copy, $flag);
        return $this->newInstance($copy);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortValuesDesc(int $flag = SORT_REGULAR): static
    {
        $copy = $this->toArray();
        arsort($copy, $flag);
        return $this->newInstance($copy);
    }

    /**
     * @param callable $comparison
     * @return static
     */
    public function sortWith(callable $comparison): static
    {
        $copy = $this->toArray();
        uasort($copy, $comparison);
        return $this->newInstance($copy);
    }

    /**
     * @return float|int
     */
    public function sum(): float|int
    {
        return Arr::sum($this->items);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function take(int $amount): static
    {
        return $this->newInstance(Arr::take($this->items, $amount));
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function takeUntil(callable $condition): static
    {
        return $this->newInstance(Arr::takeUntil($this->items, $condition));
    }

    /**
     * @param callable $condition
     * @return static
     */
    public function takeWhile(callable $condition): static
    {
        return $this->newInstance(Arr::takeWhile($this->items, $condition));
    }

    /**
     * @return Collection
     */
    public function tally(): Collection
    {
        return $this->newCollection(Arr::tally($this->items));
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->asArray($this->items);
    }

    /**
     * @param int|null $depth
     * @return array
     */
    public function toArrayRecursive(?int $depth = null): array
    {
        return $this->asArrayRecursive($this->items, $depth ?? PHP_INT_MAX, true);
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
     * @param iterable $iterable
     * @return $this
     */
    public function union(iterable $iterable): static
    {
        return $this->newInstance(Arr::union($this->items, $iterable));
    }

    /**
     * @param iterable $iterable
     * @param int $depth
     * @return $this
     */
    public function unionRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::unionRecursive($this->items, $iterable, $depth));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function unique(int $flag = SORT_STRING): static
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
    protected function asArrayRecursive(iterable $items, int $depth, bool $validate = false): array
    {
        if ($validate) {
            Assert::positiveInt($depth);
        }

        return Arr::map($items, function($item) use ($depth) {
            return (is_iterable($item) && $depth > 1) ? $this->asArrayRecursive($item, $depth - 1) : $item;
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
}
