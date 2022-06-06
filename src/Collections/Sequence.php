<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Countable;
use JsonSerializable;
use Kirameki\Support\Concerns\Tappable;
use Kirameki\Support\Json;
use Symfony\Component\VarDumper\VarDumper;
use Webmozart\Assert\Assert;
use function is_iterable;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends Iterator<TKey, TValue>
 */
class Sequence extends Iterator implements Countable, JsonSerializable
{
    use Tappable;

    protected bool $isList;

    /**
     * @param iterable<TKey, TValue>|null $items
     * @param bool $isList
     */
    public function __construct(iterable|null $items = null, bool $isList = false)
    {
        parent::__construct($items ?? []);
        $this->isList = $isList;
    }

    /**
     * @template TNewKey of array-key
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $items
     * @return static<TNewKey, TNewValue>
     */
    public function newInstance(mixed $items): static
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
     * @param int $position
     * @return TValue|null
     */
    public function at(int $position)
    {
        return Arr::at($this, $position);
    }

    /**
     * @template TDefault
     * @param int $position
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function atOr(int $position, mixed $default)
    {
        return Arr::atOr($this, $position, $default);
    }

    /**
     * @param int $position
     * @return TValue
     */
    public function atOrFail(int $position)
    {
        return Arr::atOrFail($this, $position);
    }

    /**
     * @param bool $allowEmpty
     * @return float|int
     */
    public function average(bool $allowEmpty = true): float|int
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
     * @return Vec<static>
     */
    public function chunk(int $size): self
    {
        $chunks = [];
        foreach (Iter::chunk($this, $size, $this->isList) as $chunk) {
            /** @var static $converted */
            $converted = $this->newInstance($chunk);
            $chunks[] = $converted;
        }
        return new Vec($chunks);
    }

    /**
     * @param int<1, max> $depth
     * @return static
     */
    public function compact(int $depth = 1): static
    {
        return $this->newInstance(Arr::compact($this, $depth, $this->isList));
    }

    /**
     * @param mixed|Closure(TValue, TKey): bool $value
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return Arr::contains($this, $value);
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
     * @param Closure(TValue, TKey): bool $condition
     * @return int
     */
    public function countBy(Closure $condition): int
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
        return $this->newInstance(Arr::diff($this, $items, $this->isList));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function drop(int $amount): static
    {
        return $this->newInstance(Iter::drop($this, $amount, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->newInstance(Iter::dropUntil($this, $condition, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->newInstance(Iter::dropWhile($this, $condition, $this->isList));
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
     * @return static
     */
    public function duplicates(): static
    {
        return $this->newInstance(Arr::duplicates($this));
    }

    /**
     * @param Closure(TValue, TKey): void $callback
     * @return $this
     */
    public function each(Closure $callback): static
    {
        Arr::each($this, $callback);
        return $this;
    }

    /**
     * @param mixed $items
     * @return bool
     */
    public function equals(mixed $items): bool
    {
        if (is_iterable($items)) {
            /** @var iterable<array-key, mixed> $items */
            return $this->toArray() === Arr::from($items);
        }
        return false;
    }

    /**
     * @param array<TKey> $keys
     * @return static
     */
    public function except(iterable $keys): static
    {
        return $this->newInstance(Arr::except($this, $keys, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function filter(Closure $condition): static
    {
        return $this->newInstance(Arr::filter($this, $condition, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function first(?Closure $condition = null): mixed
    {
        return Arr::first($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey):bool $condition
     * @return int|null
     */
    public function firstIndex(Closure $condition): ?int
    {
        return Arr::firstIndex($this, $condition);
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function firstOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::firstOr($this, $default, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function firstOrFail(?Closure $condition = null): mixed
    {
        return Arr::firstOrFail($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): mixed $callback
     * @return Vec<mixed>
     */
    public function flatMap(Closure $callback): Vec
    {
        return new Vec(Arr::flatMap($this, $callback));
    }

    /**
     * @param int<1, max> $depth
     * @return Vec<mixed>
     */
    public function flatten(int $depth = 1): Vec
    {
        return new Vec(Arr::flatten($this, $depth));
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
     * @param Closure(U, TValue, TKey): U $callback
     * @return U
     */
    public function fold(mixed $initial, Closure $callback): mixed
    {
        return Arr::fold($this, $initial, $callback);
    }

    /**
     * @template TGroupKey of array-key
     * @param Closure(TValue, TKey): TGroupKey|TGroupKey $key
     * @return Map<TGroupKey, static>
     */
    public function groupBy(int|string|Closure $key): Map
    {
        $grouped = Arr::groupBy($this, $key, $this->isList);
        return (new Map($grouped))->map(function(array $group): static {
            return $this->newInstance($group);
        });
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->newInstance(Arr::intersect($this, $items, $this->isList));
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
     * @template TKeyBy of array-key
     * @param string|Closure(TValue, TKey): TKeyBy $key
     * @param bool $overwrite
     * @return Map<TKeyBy, TValue>
     */
    public function keyBy(string|Closure $key, bool $overwrite = false): Map
    {
        return new Map(Arr::keyBy($this, $key, $overwrite));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function last(?Closure $condition = null): mixed
    {
        return Arr::last($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return int|null
     */
    public function lastIndex(Closure $condition): ?int
    {
        return Arr::lastIndex($this, $condition);
    }

    /**
     * @template TDefault
     * @param Closure(TValue, TKey): bool|null $condition
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function lastOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::lastOr($this, $default, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function lastOrFail(?Closure $condition = null): mixed
    {
        return Arr::lastOrFail($this, $condition);
    }

    /**
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return static<TKey, TMapValue>
     */
    public function map(Closure $callback): static
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
     * @param Closure(TValue, TKey): mixed $callback
     * @return TValue
     */
    public function maxBy(Closure $callback): mixed
    {
        return Arr::maxBy($this, $callback);
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function merge(iterable $iterable): static
    {
        return $this->newInstance(Arr::merge($this, $iterable, $this->isList));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function mergeRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::mergeRecursive($this, $iterable, $depth, $this->isList));
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
     * @param Closure(TValue, TKey): mixed $callback
     * @return TValue|null
     */
    public function minBy(Closure $callback): mixed
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
        return $this->newInstance(Arr::only($this, $keys, $this->isList));
    }

    /**
     * Move items that match condition to the top of the array.
     *
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function prioritize(Closure $condition): static
    {
        return $this->newInstance(Arr::prioritize($this, $condition, $this->isList));
    }

    /**
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * @return TValue
     */
    public function reduce(Closure $callback): mixed
    {
        return Arr::reduce($this, $callback);
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return $this->newInstance(Arr::reverse($this, $this->isList));
    }

    /**
     * @param int $count
     * @return static
     */
    public function rotate(int $count): static
    {
        return $this->newInstance(Arr::rotate($this, $count, $this->isList));
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
        return $this->newInstance(Arr::sampleMany($this, $amount, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAll(Closure $condition): bool
    {
        return Arr::satisfyAll($this, $condition);
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public function satisfyAny(Closure $condition): bool
    {
        return Arr::satisfyAny($this, $condition);
    }

    /**
     * @return static
     */
    public function shuffle(): static
    {
        return $this->newInstance(Arr::shuffle($this, $this->isList));
    }

    /**
     * @param int $offset
     * @param int $length
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->newInstance(Iter::slice($this, $offset, $length, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function sole(?Closure $condition = null): mixed
    {
        return Arr::sole($this, $condition);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sort(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sort($this, $flag, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): mixed $callback
     * @param int $flag
     * @return static
     */
    public function sortBy(Closure $callback, int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortBy($this, $callback, $flag, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): mixed $callback
     * @param int $flag
     * @return static
     */
    public function sortByDesc(Closure $callback, int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortByDesc($this, $callback, $flag, $this->isList));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKey(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortByKey($this, $flag));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortByKeyDesc(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortByKeyDesc($this, $flag));
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sortDesc(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortDesc($this, $flag, $this->isList));
    }

    /**
     * @param Closure(TValue, TValue): int $comparison
     * @return static
     */
    public function sortWith(Closure $comparison): static
    {
        return $this->newInstance(Arr::sortWith($this, $comparison, $this->isList));
    }

    /**
     * @param Closure(TKey, TKey): int $comparison
     * @return static
     */
    public function sortWithKey(Closure $comparison): static
    {
        return $this->newInstance(Arr::sortWithKey($this, $comparison));
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
        return $this->newInstance(Iter::take($this, $amount));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->newInstance(Iter::takeUntil($this, $condition));
    }

    /**
     * @param Closure(TValue, TKey): bool $condition
     * @return static
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->newInstance(Iter::takeWhile($this, $condition));
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
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    public function union(iterable $iterable): static
    {
        return $this->newInstance(Arr::union($this, $iterable, $this->isList));
    }

    /**
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return static
     */
    public function unionRecursive(iterable $iterable, int $depth = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::unionRecursive($this, $iterable, $depth, $this->isList));
    }

    /**
     * @return static
     */
    public function unique(): static
    {
        return $this->newInstance(Arr::unique($this, $this->isList));
    }

    /**
     * @param Closure(TValue, TKey): bool $callback
     * @return static
     */
    public function uniqueBy(Closure $callback): static
    {
        return $this->newInstance(Arr::uniqueBy($this, $callback, $this->isList));
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
