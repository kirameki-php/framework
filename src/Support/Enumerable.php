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
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Enumerable implements Countable, IteratorAggregate, JsonSerializable
{
    use Concerns\Macroable;
    use Concerns\Tappable;

    /**
     * @var iterable<TKey, TValue>
     */
    protected iterable $items;

    /**
     * @param iterable<TKey, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        $this->items = $items ?? [];
    }

    /**
     * @template TNewKey of array-key
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $items
     * @return static<TNewKey, TNewValue>
     */
    public function newInstance(iterable $items = []): static /** @phpstan-ignore-line */
    {
        return new static($items);
    }

    /**
     * @return array<mixed>
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
     * @return Generator<TKey, TValue>
     */
    public function getIterator(): Generator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * @return array<TKey, TValue>
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    /**
     * @param int $position
     * @return TValue|null
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
     * @return TValue|null
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this->items);
    }

    /**
     * @return TValue
     */
    public function coalesceOrFail(): mixed
    {
        return Arr::coalesceOrFail($this->items);
    }

    /**
     * @param int<1, max> $size
     * @return static<int, static<TKey, TValue>>
     */
    public function chunk(int $size): self
    {
        $array = $this->toArray();
        $chunks = [];
        foreach (array_chunk($array, $size, Arr::isAssoc($array)) as $chunk) {
            /** @var static<TKey, TValue> $chunk */
            $converted = $this->newInstance($chunk);
            $chunks[] = $converted;
        }
        return $this->newInstance($chunks);
    }

    /**
     * @param int<1, max> $depth
     * @return static
     */
    public function compact(int $depth = 1): static
    {
        return $this->newInstance(Arr::compact($this->items, $depth));
    }

    /**
     * @param mixed|callable(TValue, TKey): bool $value
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
     * @param callable(TValue, TKey): bool $condition
     * @return int
     */
    public function countBy(callable $condition): int
    {
        return Arr::countBy($this->items, $condition);
    }

    /**
     * @return Generator<TKey, TValue>
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
        $this->dump($asArray);
        exit(1);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diff(iterable $items): static
    {
        return $this->newInstance(array_diff($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable<TKey, TValue> $items
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
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(callable $condition): static
    {
        return $this->newInstance(Arr::dropUntil($this->items, $condition));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
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
     * @param callable (TValue, TKey): void|mixed $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        Arr::each($this->items, $callback);
        return $this;
    }

    /**
     * @param int $size
     * @param callable (static<TKey, TValue>, int): void $callback
     * @return $this
     */
    public function eachChunk(int $size, callable $callback): static
    {
        Arr::eachChunk($this->items, $size, function(array $items, int $count) use ($callback) {
            $instance = $this->newInstance($items);
            $callback($instance, $count);
        });
        return $this;
    }

    /**
     * @param callable(TValue, TKey, int): void $callback
     * @return $this
     */
    public function eachWithIndex(callable $callback): static
    {
        Arr::eachWithIndex($this->items, $callback);
        return $this;
    }

    /**
     * @param mixed $items
     * @return bool
     */
    public function equals(mixed $items): bool
    {
        return is_iterable($items) && ($this->toArray() === $this->asArray($items)); /** @phpstan-ignore-line */
    }
    /**
     * @param array<TKey> $keys
     * @return static
     */
    public function except(iterable $keys): static
    {
        return $this->newInstance(Arr::except($this->items, $keys));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function filter(callable $condition): static
    {
        return $this->newInstance(Arr::filter($this->items, $condition));
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function first(callable $condition = null): mixed
    {
        return Arr::first($this->items, $condition);
    }

    /**
     * @param callable(TValue, TKey):bool $condition
     * @return int|null
     */
    public function firstIndex(callable $condition): ?int
    {
        return Arr::firstIndex($this->items, $condition);
    }

    /**
     * @param callable(TValue, TKey):bool | null $condition
     * @return TKey|null
     */
    public function firstKey(callable $condition = null): mixed
    {
        return Arr::firstKey($this->items, $condition);
    }

    /**
     * @param callable(TValue, TKey):bool | null $condition
     * @return TValue
     */
    public function firstOrFail(callable $condition = null): mixed
    {
        return Arr::firstOrFail($this->items, $condition);
    }

    /**
     * @param callable(TValue, TKey):mixed $callable
     * @return static
     */
    public function flatMap(callable $callable): static
    {
        return $this->newInstance(Arr::flatMap($this->items, $callable));
    }

    /**
     * @param int<1, max> $depth
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
     * @template U
     * @param U $initial
     * @param callable(U, TValue, TKey): U $callback
     * @return U
     */
    public function fold(mixed $initial, callable $callback): mixed
    {
        return Arr::fold($this->items, $initial, $callback);
    }

    /**
     * @param int|string $key
     * @return TValue|null
     */
    public function get(int|string $key): mixed
    {
        return Arr::get($this->items, $key);
    }

    /**
     * @param string|callable(TValue, TKey): array-key $key
     * @return Collection<array-key, static>
     */
    public function groupBy(string|callable $key): Collection
    {
        return $this->newCollection(Arr::groupBy($this->items, $key))->map(fn($array) => $this->newInstance($array));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->newInstance(array_intersect($this->toArray(), $this->asArray($items)));
    }

    /**
     * @param iterable<TKey, TValue> $items
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
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public function join(string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return Arr::join($this->items, $glue, $prefix, $suffix);
    }

    /**
     * @param string|callable(TValue, mixed): array-key $key
     * @param bool $overwrite
     * @return static<array-key, TValue>
     */
    public function keyBy(string|callable $key, bool $overwrite = false): static /** @phpstan-ignore-line */
    {
        return $this->newInstance(Arr::keyBy($this->items, $key, $overwrite));
    }

    /**
     * @return Collection<int, TKey>
     */
    public function keys(): Collection
    {
        return $this->newCollection(Arr::keys($this->items));
    }

    /**
     * @param callable|null $condition
     * @return TValue|null
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
     * @param callable(TValue, TKey):bool | null $condition
     * @return mixed
     */
    public function lastKey(callable $condition = null): mixed
    {
        return Arr::lastKey($this->items, $condition);
    }

    /**
     * @param callable(TValue, TKey):bool | null $condition
     * @return TValue
     */
    public function lastOrFail(callable $condition = null): mixed
    {
        return Arr::lastOrFail($this->items, $condition);
    }

    /**
     * @template TNew
     * @param callable(TValue, TKey): TNew $callback
     * @return Collection<TKey, TNew>
     */
    public function map(callable $callback): Collection
    {
        return $this->newCollection(Arr::map($this->items, $callback));
    }

    /**
     * @return TValue
     */
    public function max(): mixed
    {
        return Arr::max($this->items);
    }

    /**
     * @return TValue|null
     */
    public function maxBy(callable $callback): mixed
    {
        return Arr::maxBy($this->items, $callback);
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function merge(iterable $iterable): static
    {
        return $this->newInstance(Arr::merge($this->items, $iterable));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::mergeRecursive($this->items, $iterable, $depth));
    }

    /**
     * Returns the minimum element in the sequence.
     *
     * @return TValue
     */
    public function min(): mixed
    {
        return Arr::min($this->items);
    }

    /**
     * Returns the minimum element in the sequence using the given predicate as the comparison between elements.
     *
     * @return TValue|null
     */
    public function minBy(callable $callback): mixed
    {
        return Arr::minBy($this->items, $callback);
    }

    /**
     * @return array<TValue>
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
     * @param iterable<TKey> $keys
     * @return static
     */
    public function only(iterable $keys): static
    {
        return $this->newInstance(Arr::only($this->items, $keys));
    }

    /**
     * Move items that match condition to the top of the array.
     *
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function prioritize(callable $condition): static
    {
        return $this->newInstance(Arr::prioritize($this->items, $condition));
    }

    /**
     * @param callable(TValue, TValue, TKey): TValue $callback
     * @return TValue
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
     * @return TValue|null
     */
    public function sample(): mixed
    {
        return Arr::sample($this->items);
    }

    /**
     * @param int $amount
     * @return Collection<int, TValue>
     */
    public function sampleMany(int $amount): Collection
    {
        return $this->newCollection(Arr::sampleMany($this->items, $amount));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAll(callable $condition): bool
    {
        return Arr::satisfyAll($this->items, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool $condition
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
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function sole(callable $condition = null): mixed
    {
        return Arr::sole($this->items, $condition);
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
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @return static
     */
    public function sortBy(callable $callback, int $flag = SORT_REGULAR): static
    {
        return $this->sortByInternal($callback, $flag, true);
    }

    /**
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @return static
     */
    public function sortByDesc(callable $callback, int $flag = SORT_REGULAR): static
    {
        return $this->sortByInternal($callback, $flag, false);
    }

    /**
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @param bool $ascending
     * @return static
     */
    protected function sortByInternal(callable $callback, int $flag, bool $ascending): static
    {
        $copy = $this->toArray();

        $refs = [];
        foreach ($copy as $key => $item) {
            $refs[$key] = $callback($item, $key);
        }

        $ascending
            ? asort($refs, $flag)
            : arsort($refs, $flag);

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
        return Arr::sum($this->items); /** @phpstan-ignore-line */
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
     * @return Collection<array-key, int<0, max>>
     */
    public function tally(): Collection
    {
        return $this->newCollection(Arr::tally($this->items));
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->asArray($this->items);
    }

    /**
     * @param int<1, max>|null $depth
     * @return array<TKey, TValue>
     */
    public function toArrayRecursive(?int $depth = null): array
    {
        return $this->asArrayRecursive($this->items, $depth ?? PHP_INT_MAX, true);
    }

    /**
     * @param int $options
     * @param int<1, max> $depth
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
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function union(iterable $iterable): static
    {
        return $this->newInstance(Arr::union($this->items, $iterable));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
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
     * @param callable $callback
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
     * @template TNewKey of array-key
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $items
     * @return array<TNewKey, TNewValue>
     */
    protected function asArray(iterable $items): array
    {
        return Arr::from($items);
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @param int $depth
     * @param bool $validate
     * @return array<TKey, TValue>
     */
    protected function asArrayRecursive(iterable $items, int $depth, bool $validate = false): array
    {
        if ($validate) {
            Assert::positiveInt($depth);
        }

        return Arr::map($items, function($item) use ($depth) {
            return (is_iterable($item) && $depth > 1) ? $this->asArrayRecursive($item, $depth - 1) : $item; /** @phpstan-ignore-line */
        });
    }

    /**
     * @template UKey of array-key
     * @template UValue
     * @param iterable<UKey, UValue> $items
     * @return Collection<UKey, UValue>
     */
    protected function newCollection(iterable $items): Collection
    {
        return new Collection($items);
    }
}
