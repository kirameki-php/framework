<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Countable;
use JsonSerializable;
use Symfony\Component\VarDumper\VarDumper;
use Webmozart\Assert\Assert;
use function is_iterable;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 * @extends ItemIterator<TKey, TValue>
 */
class Sequence extends ItemIterator implements Countable, JsonSerializable
{
    use Concerns\Macroable;
    use Concerns\Tappable;

    /**
     * @param iterable<TKey, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        parent::__construct($items ?? []);
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
     * @return TValue
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
     * @param bool|null $allowEmpty
     * @return float|int
     */
    public function average(?bool $allowEmpty = true): float|int
    {
        return Arr::average($this, $allowEmpty);
    }

    /**
     * @return TValue
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this);
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function coalesceOr(mixed $default): mixed
    {
        return Arr::coalesceOr($this, $default);
    }

    /**
     * @param int<1, max> $size
     * @return self<int, static>
     */
    public function chunk(int $size): self
    {
        $chunks = [];
        foreach (Arr::chunk($this, $size) as $chunk) {
            /** @var static $converted */
            $converted = $this->newInstance($chunk);
            $chunks[] = $converted;
        }
        return new self($chunks);
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
        return Arr::containsKey($this, $key);
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
        return $this->newInstance(Arr::diff($this, $items));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function diffKeys(iterable $items): static
    {
        return $this->newInstance(Arr::diffKeys($this, $items));
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
     * @return TValue
     */
    public function first(?callable $condition = null): mixed
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
    public function firstKey(?callable $condition = null): mixed
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * @template TDefault
     * @param TDefault $default
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public function firstOr(mixed $default, ?callable $condition = null): mixed
    {
        return Arr::firstOr($this, $default, $condition);
    }

    /**
     * @param callable(TValue, TKey): mixed $callable
     * @return static<int, mixed>
     */
    public function flatMap(callable $callable): static
    {
        return $this->newInstance(Arr::flatMap($this, $callable));
    }

    /**
     * @param int<1, max> $depth
     * @return static<int, mixed>
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
     * @template TGroupKey of array-key
     * @param Closure(TValue, TKey): TGroupKey|TGroupKey $key
     * @return Sequence<TGroupKey, static>
     */
    public function groupBy(int|string|Closure $key): Sequence
    {
        $grouped = Arr::groupBy($this, $key);
        return (new self($grouped))->map(function(array $group): static {
            return $this->newInstance($group);
        });
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->newInstance(Arr::intersect($this, $items));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @return static
     */
    public function intersectKeys(iterable $items): static
    {
        return $this->newInstance(Arr::intersectKeys($this, $items));
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
     * @param string|Closure(TValue, TKey): array-key $key
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
     * @return TValue
     */
    public function last(?callable $condition = null): mixed
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
    public function lastKey(?callable $condition = null): mixed
    {
        return Arr::lastKey($this, $condition);
    }

    /**
     * @template TDefault
     * @param callable(TValue, TKey): bool|null $condition
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function lastOr(mixed $default, ?callable $condition = null): mixed
    {
        return Arr::lastOr($this, $default, $condition);
    }

    /**
     * @template TMapValue
     * @param callable(TValue, TKey): TMapValue $callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback): static
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
     * @param callable(TValue, TKey): mixed $callback
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
     * @param callable(TValue, TKey): mixed $callback
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
     * @param int $length
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->newInstance(Arr::slice($this, $offset, $length));
    }

    /**
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public function sole(?callable $condition = null): mixed
    {
        return Arr::sole($this, $condition);
    }

    /**
     * @param int $flag
     * @return static
     */
    public function sort(int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sort($this, $flag));
    }

    /**
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @return static
     */
    public function sortBy(callable $callback, int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortBy($this, $callback, $flag));
    }

    /**
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @return static
     */
    public function sortByDesc(callable $callback, int $flag = SORT_REGULAR): static
    {
        return $this->newInstance(Arr::sortByDesc($this, $callback, $flag));
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
        return $this->newInstance(Arr::sortDesc($this, $flag));
    }

    /**
     * @param callable(TValue, TValue): int $comparison
     * @return static
     */
    public function sortWith(callable $comparison): static
    {
        return $this->newInstance(Arr::sortWith($this, $comparison));
    }

    /**
     * @param callable(TKey, TKey): int $comparison
     * @return static
     */
    public function sortWithKey(callable $comparison): static
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
     * @return static<int, TValue>
     */
    public function values(): static
    {
        return $this->newInstance(Arr::values($this));
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
