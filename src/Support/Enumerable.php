<?php declare(strict_types=1);

namespace Kirameki\Support;

use Countable;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use Symfony\Component\VarDumper\VarDumper;
use function array_chunk;
use function array_diff;
use function array_diff_key;
use function array_intersect;
use function array_intersect_key;
use function array_slice;
use function arsort;
use function asort;
use function is_iterable;
use function krsort;
use function ksort;

/**
 * @template T
 */
class Enumerable implements Countable, IteratorAggregate, JsonSerializable
{
    use Concerns\Macroable;
    use Concerns\Tappable;

    /**
     * @var iterable<T>
     */
    protected iterable $items;

    /**
     * @param iterable|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        $items ??= [];
        $this->items = $items;
    }

    /**
     * @param iterable<T>|null $items
     * @return self<T>
     */
    public function newInstance(iterable|null $items = null): self
    {
        return new self($items);
    }

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
     * @return Generator<T>
     */
    public function getIterator(): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * @param int $position
     * @return T|null
     */
    public function at(int $position)
    {
        return Arr::at($this->items, $position);
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
     * @return T|null
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this->items);
    }

    /**
     * @return T
     */
    public function coalesceOrFail(): mixed
    {
        return Arr::coalesceOrFail($this->items);
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
     * @return static
     */
    public function compact(int $depth = 1): static
    {
        return $this->newInstance(Arr::compact($this->items, $depth));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function contains(mixed $value): bool
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
        return clone $this;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return Arr::count($this->items);
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
        if ($this->dump($asArray)) {
            exit(1);
        }
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
        VarDumper::dump($asArray ? $this->toArray() : $this);
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        Arr::each($this->items, $callback);
        return $this;
    }

    /**
     * @param int $size
     * @param callable $callback
     * @return $this
     */
    public function eachChunk(int $size, callable $callback): static
    {
        Arr::eachChunk($this->items, $size, function(array $items, int $count) use ($callback) {
            $callback($this->newInstance($items), $count);
        });
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function eachWithIndex(callable $callback): static
    {
        Arr::eachWithIndex($this->items, $callback);
        return $this;
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
     * @return T|null
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
     * @param callable|null $condition
     * @return T
     */
    public function firstOrFail(callable $condition = null): mixed
    {
        return Arr::firstOrFail($this->items, $condition);
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
     * @return static
     */
    public function flip(bool $overwrite = false): static
    {
        return $this->newInstance(Arr::flip($this->items, $overwrite));
    }

    /**
     * @template T_INIT
     * @param T_INIT|null $initial
     * @param callable $callback
     * @return T_INIT
     */
    public function fold(mixed $initial, callable $callback): mixed
    {
        return Arr::fold($this->items, $initial, $callback);
    }

    /**
     * @param int|string $key
     * @return T|null
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
        return $this->newCollection(Arr::groupBy($this->items, $key))->map(fn($array) => $this->newInstance($array));
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
     * @return static
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
        return $this->newCollection(Arr::keys($this->items));
    }

    /**
     * @param callable|null $condition
     * @return T|null
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
     * @param callable|null $condition
     * @return T
     */
    public function lastOrFail(callable $condition = null): mixed
    {
        return Arr::lastOrFail($this->items, $condition);
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        return $this->newCollection(Arr::map($this->items, $callback));
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
     * Move items that match condition to the top of the array.
     *
     * @param callable $condition
     * @return static
     */
    public function prioritize(callable $condition): static
    {
        return $this->newInstance(Arr::prioritize($this->items, $condition));
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public function reduce(callable $callback): mixed
    {
        return Arr::reduce($this->items, $callback);
    }

    /**
     * @param int $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->newInstance(Arr::repeat($this->items, $times));
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->newInstance(Arr::reverse($this->items));
    }

    /**
     * @return T|null
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
        return $this->newCollection(Arr::sampleMany($this->items, $amount));
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
     * @return static
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
    public function sortDesc(int $flag = SORT_REGULAR): static
    {
        $copy = $this->toArray();
        arsort($copy, $flag);
        return $this->newInstance($copy);
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
     * @return array<T>
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
     * @return static
     */
    public function union(iterable $iterable): static
    {
        return $this->newInstance(Arr::union($this->items, $iterable));
    }

    /**
     * @param iterable $iterable
     * @param int $depth
     * @return static
     */
    public function unionRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::unionRecursive($this->items, $iterable, $depth));
    }

    /**
     * @return static
     */
    public function unique(): static
    {
        return $this->newInstance(Arr::unique($this->items));
    }

    /**
     * @return static
     */
    public function uniqueBy(callable $callback): static
    {
        return $this->newInstance(Arr::uniqueBy($this->items, $callback));
    }

    /**
     * @return static
     */
    public function values(): static
    {
        return $this->newInstance(Arr::values($this->toArray()));
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
     * @param int $depth
     * @param bool $validate
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
