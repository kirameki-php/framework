<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Symfony\Component\VarDumper\VarDumper;
use Webmozart\Assert\Assert;
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
 * @template TKey of array-key|class-string
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Sequence implements Countable, IteratorAggregate, JsonSerializable
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
     * @param iterable<TNewKey, TNewValue> $args
     * @return static<TNewKey, TNewValue>
     */
    public function newInstance(mixed $items): static /** @phpstan-ignore-line */
    {
        return new static($items);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $values = [];
        foreach ($this as $key => $item) {
            $values[$key] = ($item instanceof JsonSerializable) ? $item->jsonSerialize() : $item;
        }
        return $values;
    }

    /**
     * @return Iterator<TKey, TValue>
     */
    public function getIterator(): Iterator
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * @param int $position
     * @return TValue|null
     */
    public function at(int $position)
    {
        return Arr::at($this, $position);
    }

    /**
     * @param bool|null $allowEmpty
     * @return float|int
     */
    public function average(?bool $allowEmpty = true): float|int
    {
        return Arr::average($this, $allowEmpty);
    }

    /**
     * @return TValue|null
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this);
    }

    /**
     * @return TValue
     */
    public function coalesceOrFail(): mixed
    {
        return Arr::coalesceOrFail($this);
    }

    /**
     * @param int<1, max> $size
     * @return static<int, Collection<TKey, TValue>>
     */
    public function chunk(int $size): static /** @phpstan-ignore-line */
    {
        $array = $this->toArray();
        $chunks = [];
        foreach (Arr::chunk($array, $size) as $chunk) {
            $converted = new Collection($chunk);
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
        return $this->newInstance(Arr::compact($this, $depth));
    }

    /**
     * @param mixed|callable(TValue, TKey): bool $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return Arr::contains($this, $value);
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
        return Arr::count($this->toArray());
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return int
     */
    public function countBy(callable $condition): int
    {
        return Arr::countBy($this, $condition);
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
        return $this->newInstance(Arr::drop($this, $amount));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(callable $condition): static
    {
        return $this->newInstance(Arr::dropUntil($this, $condition));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function dropWhile(callable $condition): static
    {
        return $this->newInstance(Arr::dropWhile($this, $condition));
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
     * @param callable(TValue, TKey): void $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        Arr::each($this, $callback);
        return $this;
    }

    /**
     * @param callable(TValue, TKey, int): void $callback
     * @return $this
     */
    public function eachWithIndex(callable $callback): static
    {
        Arr::eachWithIndex($this, $callback);
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
        return $this->newInstance(Arr::except($this, $keys));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function filter(callable $condition): static
    {
        return $this->newInstance(Arr::filter($this, $condition));
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function first(callable $condition = null): mixed
    {
        return Arr::first($this, $condition);
    }

    /**
     * @param callable(TValue, TKey):bool $condition
     * @return int|null
     */
    public function firstIndex(callable $condition): ?int
    {
        return Arr::firstIndex($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public function firstKey(callable $condition = null): mixed
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function firstOrFail(callable $condition = null): mixed
    {
        return Arr::firstOrFail($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): mixed $callable
     * @return static
     */
    public function flatMap(callable $callable): static
    {
        return $this->newInstance(Arr::flatMap($this, $callable));
    }

    /**
     * @param int<1, max> $depth
     * @return static
     */
    public function flatten(int $depth = 1): static
    {
        return $this->newInstance(Arr::flatten($this, $depth));
    }

    /**
     * @param bool $overwrite
     * @return static
     */
    public function flip(bool $overwrite = false): static
    {
        return $this->newInstance(Arr::flip($this, $overwrite));
    }

    /**
     * @template U
     * @param U $initial
     * @param callable(U, TValue, TKey): U $callback
     * @return U
     */
    public function fold(mixed $initial, callable $callback): mixed
    {
        return Arr::fold($this, $initial, $callback);
    }

    /**
     * @param int|string $key
     * @return TValue|null
     */
    public function get(int|string $key): mixed
    {
        return Arr::get($this, $key);
    }

    /**
     * @param string|Closure(TValue, TKey): array-key $key
     * @return static<array-key, static>
     */
    public function groupBy(string|Closure $key): static /** @phpstan-ignore-line */
    {
        return $this->newInstance(Arr::groupBy($this, $key))->map(fn($array) => $this->newInstance($array));
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
        return Arr::isAssoc($this);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return Arr::isEmpty($this);
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return Arr::isList($this);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return Arr::isNotEmpty($this);
    }

    /**
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public function join(string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return Arr::join($this, $glue, $prefix, $suffix);
    }

    /**
     * @param string|Closure(TValue, mixed): array-key $key
     * @param bool $overwrite
     * @return static<array-key, TValue>
     */
    public function keyBy(string|Closure $key, bool $overwrite = false): static
    {
        return $this->newInstance(Arr::keyBy($this, $key, $overwrite));
    }

    /**
     * @return static<int, TKey>
     */
    public function keys(): static
    {
        return $this->newInstance(Arr::keys($this));
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function last(callable $condition = null): mixed
    {
        return Arr::last($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return int|null
     */
    public function lastIndex(callable $condition): ?int
    {
        return Arr::lastIndex($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return mixed
     */
    public function lastKey(callable $condition = null): mixed
    {
        return Arr::lastKey($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function lastOrFail(callable $condition = null): mixed
    {
        return Arr::lastOrFail($this, $condition);
    }

    /**
     * @template TNew
     * @param callable(TValue, TKey): TNew $callback
     * @return static<TKey, TNew>
     */
    public function map(callable $callback): static /** @phpstan-ignore-line */
    {
        return $this->newInstance(Arr::map($this, $callback));
    }

    /**
     * @return TValue
     */
    public function max(): mixed
    {
        return Arr::max($this);
    }

    /**
     * @return TValue|null
     */
    public function maxBy(callable $callback): mixed
    {
        return Arr::maxBy($this, $callback);
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function merge(iterable $iterable): static
    {
        return $this->newInstance(Arr::merge($this, $iterable));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::mergeRecursive($this, $iterable, $depth));
    }

    /**
     * Returns the minimum element in the sequence.
     *
     * @return TValue
     */
    public function min(): mixed
    {
        return Arr::min($this);
    }

    /**
     * Returns the minimum element in the sequence using the given predicate as the comparison between elements.
     *
     * @return TValue|null
     */
    public function minBy(callable $callback): mixed
    {
        return Arr::minBy($this, $callback);
    }

    /**
     * @return array<TValue>
     */
    public function minMax(): array
    {
        return Arr::minMax($this);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function notContains(mixed $value): bool
    {
        return Arr::notContains($this, $value);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function notContainsKey(int|string $key): bool
    {
        return Arr::notContainsKey($this, $key);
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
        return $this->newInstance(Arr::only($this, $keys));
    }

    /**
     * Move items that match condition to the top of the array.
     *
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function prioritize(callable $condition): static
    {
        return $this->newInstance(Arr::prioritize($this, $condition));
    }

    /**
     * @param callable(TValue, TValue, TKey): TValue $callback
     * @return TValue
     */
    public function reduce(callable $callback): mixed
    {
        return Arr::reduce($this, $callback);
    }

    /**
     * @param int $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->newInstance(Arr::repeat($this, $times));
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->newInstance(Arr::reverse($this));
    }

    /**
     * @param int $count
     * @return static
     */
    public function rotate(int $count): static
    {
        return $this->newInstance(Arr::rotate($this, $count));
    }

    /**
     * @return TValue|null
     */
    public function sample(): mixed
    {
        return Arr::sample($this);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function sampleMany(int $amount): static
    {
        return $this->newInstance(Arr::sampleMany($this, $amount));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAll(callable $condition): bool
    {
        return Arr::satisfyAll($this, $condition);
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAny(callable $condition): bool
    {
        return Arr::satisfyAny($this, $condition);
    }

    /**
     * @return static
     */
    public function shuffle(): static
    {
        return $this->newInstance(Arr::shuffle($this));
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
        return Arr::sole($this, $condition);
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
        return Arr::sum($this);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function take(int $amount): static
    {
        return $this->newInstance(Arr::take($this, $amount));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function takeUntil(callable $condition): static
    {
        return $this->newInstance(Arr::takeUntil($this, $condition));
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function takeWhile(callable $condition): static
    {
        return $this->newInstance(Arr::takeWhile($this, $condition));
    }

    /**
     * @return Collection<array-key, int>
     */
    public function tally(): Collection
    {
        return new Collection(Arr::tally($this));
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->asArray($this);
    }

    /**
     * @param int<1, max>|null $depth
     * @return array<TKey, TValue>
     */
    public function toArrayRecursive(?int $depth = null): array
    {
        return $this->asArrayRecursive($this, $depth ?? PHP_INT_MAX, true);
    }

    /**
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return Json::encode($this->jsonSerialize(), $options);
    }

    /**
     * @param string|null $namespace
     * @return string
     */
    public function toUrlQuery(?string $namespace = null): string
    {
        return Arr::toUrlQuery($this, $namespace);
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function union(iterable $iterable): static
    {
        return $this->newInstance(Arr::union($this, $iterable));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function unionRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::unionRecursive($this, $iterable, $depth));
    }

    /**
     * @return static
     */
    public function unique(): static
    {
        return $this->newInstance(Arr::unique($this));
    }

    /**
     * @param callable(TValue, TKey): bool $callback
     * @return static
     */
    public function uniqueBy(callable $callback): static
    {
        return $this->newInstance(Arr::uniqueBy($this, $callback));
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
            Assert::positiveInteger($depth);
        }

        return Arr::map($items, function($item) use ($depth) {
            if (is_iterable($item) && $depth > 1) {
                return $this->asArrayRecursive($item, $depth - 1);
            }
            return $item;
        });
    }
}
