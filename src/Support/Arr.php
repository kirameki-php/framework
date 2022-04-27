<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidKeyException;
use Kirameki\Exception\InvalidValueException;
use RuntimeException;
use Webmozart\Assert\Assert;
use function array_column;
use function array_diff;
use function array_diff_key;
use function array_intersect;
use function array_intersect_key;
use function array_is_list;
use function array_key_exists;
use function array_key_last;
use function array_pad;
use function array_pop;
use function array_rand;
use function array_reverse;
use function array_shift;
use function array_slice;
use function array_splice;
use function array_unshift;
use function array_values;
use function arsort;
use function asort;
use function count;
use function current;
use function end;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function key;
use function krsort;
use function ksort;
use function max;
use function min;
use function prev;
use function uasort;
use function uksort;

class Arr
{
    use Concerns\Macroable;

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $position
     * @return TValue|null
     */
    public static function at(iterable $iterable, int $position): mixed
    {
        $array = static::from($iterable);
        $offset = $position >= 0 ? $position : count($array) + $position;
        $counter = 0;

        foreach ($array as $val) {
            if ($counter === $offset) {
                return $val;
            }
            ++$counter;
        }

        return null;
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * @param bool|null $allowEmpty
     * @return float|int
     */
    public static function average(iterable $iterable, ?bool $allowEmpty = true): float|int
    {
        $size = 0;
        $sum = 0;
        foreach ($iterable as $val) {
            $sum += $val;
            ++$size;
        }

        if ($size === 0 && $allowEmpty) {
            return 0;
        }

        return $sum / $size;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $size
     * @return array<int, array<TKey, TValue>>
     */
    public static function chunk(iterable $iterable, int $size): array
    {
        Assert::positiveInteger($size);

        $retainKey = static::isAssoc($iterable);
        $remaining = $size;

        $chunks = [];
        $block = [];
        foreach ($iterable as $key => $val) {
            $retainKey
                ? $block[$key] = $val
                : $block[] = $val;

            if ((--$remaining) === 0) {
                $remaining = $size;
                $chunks[] = $block;
                $block = [];
            }
        }
        if (count($block) > 0) {
            $chunks[] = $block;
        }
        return $chunks;
    }

    /**
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return TValue
     */
    public static function coalesce(iterable $iterable): mixed
    {
        $result = static::coalesceOrNull($iterable);

        if ($result !== null) {
            return $result;
        }

        throw new InvalidValueException('not null', null);
    }

    /**
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return TValue|null
     */
    public static function coalesceOrNull(iterable $iterable): mixed
    {
        foreach ($iterable as $val) {
            if ($val !== null) {
                return $val;
            }
        }
        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int<1, max> $depth
     * @return array<TKey, TValue>
     */
    public static function compact(iterable $iterable, int $depth = 1): array
    {
        $result = [];
        foreach ($iterable as $key => $val) {
            if (is_iterable($val) && $depth > 1) {
                $val = static::compact($val, $depth - 1); /** @phpstan-ignore-line */
            }
            if ($val !== null) {
                $result[$key] = $val;
            }
        }
        return $result; /* @phpstan-ignore-line */
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param mixed $value
     * @return bool
     */
    public static function contains(iterable $iterable, mixed $value): bool
    {
        foreach ($iterable as $val) {
            if ($val === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * @param int|string $key
     * @return bool
     */
    public static function containsKey(iterable $iterable, int|string $key): bool
    {
        $array = static::from($iterable);
        return array_key_exists($key, $array);
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * @return int
     */
    public static function count(iterable $iterable): int
    {
        $countable = is_countable($iterable) ? $iterable : static::from($iterable);
        return count($countable);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return int
     */
    public static function countBy(iterable $iterable, callable $condition): int
    {
        $counter = 0;
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param iterable<TKey, TValue> $items
     * @return array<TKey, TValue>
     */
    public static function diff(iterable $iterable, iterable $items): array
    {
        return array_diff(static::from($iterable), static::from($items));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param iterable<TKey, TValue> $items
     * @return array<TKey, TValue>
     */
    public static function diffKeys(iterable $iterable, iterable $items): array
    {
        return array_diff_key(static::from($iterable), static::from($items));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $amount
     * @return array<TKey, TValue>
     */
    public static function drop(iterable $iterable, int $amount): array
    {
        return $amount >= 0
            ? array_slice(static::from($iterable), $amount)
            : array_slice(static::from($iterable), 0, -$amount);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<TKey, TValue>
     */
    public static function dropUntil(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, $condition) ?? PHP_INT_MAX;
        return static::drop($iterable, $index);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, callable $condition): array
    {
        $index = static::lastIndex($iterable, static fn($val, $key) => !static::verify($condition, $key, $val));
        return ($index !== null)
            ? static::drop($iterable, $index)
            : [];
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): void $callback
     * @return void
     */
    public static function each(iterable $iterable, callable $callback): void
    {
        foreach ($iterable as $key => $val) {
            $callback($val, $key);
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey, int): void $callback
     * @return void
     */
    public static function eachWithIndex(iterable $iterable, callable $callback): void
    {
        $offset = 0;
        foreach ($iterable as $key => $val) {
            $callback($val, $key, $offset);
            $offset++;
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param array<int|string> $keys
     * @return array<TKey, TValue>
     */
    public static function except(iterable $iterable, iterable $keys): array
    {
        $copy = static::from($iterable);
        foreach ($keys as $key) {
            unset($copy[$key]);
        }
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return array<TKey, TValue>
     */
    public static function filter(iterable $iterable, callable $condition): array
    {
        $filtered = [];
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                $filtered[$key] = $val;
            }
        }
        return $filtered;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return TValue
     */
    public static function first(iterable $iterable, ?callable $condition = null): mixed
    {
        $result = static::firstOrNull($iterable, $condition);

        if ($result === null) {
            $message = ($condition !== null)
                ? 'Failed to find matching condition.'
                : 'Iterable must contain at least one element.';
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return int|null
     */
    public static function firstIndex(iterable $iterable, callable $condition): ?int
    {
        $count = 0;
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                return $count;
            }
            $count++;
        }
        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool | null $condition
     * @return TKey|null
     */
    public static function firstKey(iterable $iterable, ?callable $condition = null): string|int|null
    {
        $condition ??= static fn() => true;

        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return TValue|null
     */
    public static function firstOrNull(iterable $iterable, ?callable $condition = null): mixed
    {
        $condition ??= static fn() => true;

        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                return $val;
            }
        }

        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): mixed $callback
     * @return array<int, mixed>
     */
    public static function flatMap(iterable $iterable, callable $callback): array
    {
        $results = [];
        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);
            if (is_iterable($result)) {
                foreach ($result as $each) {
                    $results[] = $each;
                }
            } else {
                $results[] = $result;
            }
        }
        return $results;
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * @param int<1, max> $depth
     * @return array<int, mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): array
    {
        Assert::positiveInteger($depth);

        $results = [];
        $func = static function($_iterable, int $depth) use (&$func, &$results) {
            foreach ($_iterable as $val) {
                if (is_iterable($val) && $depth > 0) {
                    $func($val, $depth - 1);
                } else {
                    $results[] = $val;
                }
            }
        };
        $func($iterable, $depth);
        return $results;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param bool $overwrite
     * @return array<array-key, TKey>
     */
    public static function flip(iterable $iterable, bool $overwrite = false): array
    {
        $flipped = [];
        foreach ($iterable as $key => $val) {
            $val = static::ensureKey($val);
            if (!$overwrite && array_key_exists($val, $flipped)) {
                throw new DuplicateKeyException($val, $key);
            }
            $flipped[$val] = $key;
        }
        return $flipped;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @template U
     * @param iterable<TKey, TValue> $iterable
     * @param U $initial
     * @param callable(U, TValue, TKey): U $callback
     * @return U
     */
    public static function fold(iterable $iterable, mixed $initial, callable $callback): mixed
    {
        $result = $initial;
        foreach ($iterable as $key => $val) {
            $result = $callback($result, $val, $key);
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterator
     * @return array<TKey, TValue>
     */
    public static function from(iterable $iterator): array
    {
        if (is_array($iterator)) {
            return $iterator;
        }
        return iterator_to_array($iterator);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int|string $key
     * @return TValue
     */
    public static function get(iterable $iterable, int|string $key): mixed
    {
        $result = static::getOrNull($iterable, $key);
        return $result ?? throw new RuntimeException("Undefined array key $key");
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int|string $key
     * @return TValue|null
     */
    public static function getOrNull(iterable $iterable, int|string $key): mixed
    {
        return static::from($iterable)[$key] ?? null;
    }

    /**
     * @template TGroupKey as array-key
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param TGroupKey|Closure(TValue, TKey): TGroupKey $key
     * @return array<TGroupKey, array<TKey, TValue>>
     */
    public static function groupBy(iterable $iterable, int|string|Closure $key): array
    {
        $callable = (is_string($key) || is_int($key))
            ? static fn(array $val, $_key) => $val[$key]
            : $key;

        $map = [];

        foreach ($iterable as $_key => $val) {
            /** @var TGroupKey $groupKey */
            $groupKey = $callable($val, $_key);
            if ($groupKey !== null) {
                Assert::validArrayKey($groupKey);
                $map[$groupKey] ??= [];
                $map[$groupKey][$_key] = $val;
            }
        }

        return $map;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int $index
     * @param T $value
     * @return void
     */
    public static function insertAt(array &$array, int $index, mixed ...$value): void
    {
        // Offset is off by one for negative indexes (Ex: -2 inserts at 3rd element from right).
        // So we add one to correct offset. If adding to one results in 0, we set it to max count
        // to put it at the end.
        if ($index < 0) {
            $index = $index === -1 ? count($array) : $index + 1;
        }
        array_splice($array, $index, 0, $value);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param iterable<TKey, TValue> $items
     * @return array<TKey, TValue>
     */
    public static function intersect(iterable $iterable, iterable $items): array
    {
        return array_intersect(static::from($iterable), static::from($items));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param iterable<TKey, TValue> $items
     * @return array<TKey, TValue>
     */
    public static function intersectKeys(iterable $iterable, iterable $items): array
    {
        return array_intersect_key(static::from($iterable), static::from($items));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return bool
     */
    public static function isAssoc(iterable $iterable): bool
    {
        if (static::isEmpty($iterable)) {
            return true;
        }
        return !static::isList($iterable);
    }

    /**
     * @param iterable<mixed> $iterable
     * @return bool
     */
    public static function isEmpty(iterable $iterable): bool
    {
        foreach ($iterable as $ignored) {
            return false;
        }
        return true;
    }

    /**
     * @param iterable<array-key, mixed> $iterable
     * @return bool
     */
    public static function isList(iterable $iterable): bool
    {
        return array_is_list(static::from($iterable));
    }

    /**
     * @param iterable<mixed> $iterable
     * @return bool
     */
    public static function isNotEmpty(iterable $iterable): bool
    {
        return !static::isEmpty($iterable);
    }

    /**
     * @param iterable<array-key, mixed> $iterable
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public static function join(iterable $iterable, string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return $prefix . implode($glue, static::from($iterable)) . $suffix;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param string|Closure(T, mixed): array-key $key
     * @param bool $overwrite
     * @return array<T>
     */
    public static function keyBy(iterable $iterable, string|Closure $key, bool $overwrite = false): array
    {
        return static::keyByRecursive($iterable, $key, $overwrite, 1);
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * @return array<int, TKey>
     */
    public static function keys(iterable $iterable): array
    {
        $keys = [];
        foreach ($iterable as $key => $_) {
            $keys[] = $key;
        }
        return $keys;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param string|Closure(T, mixed): array-key $key
     * @param bool $overwrite
     * @param int<1, max> $depth
     * @return array<T>
     */
    public static function keyByRecursive(iterable $iterable, string|Closure $key, bool $overwrite = false, int $depth = PHP_INT_MAX): array
    {
        $callable = is_string($key)
            ? static fn (): string => $key
            : $key;

        $result = [];
        foreach ($iterable as $oldKey => $val) {
            $newKey = static::ensureKey($callable($val, $oldKey));

            if (!$overwrite && array_key_exists($newKey, $result)) {
                throw new DuplicateKeyException($newKey, $val);
            }

            $result[$newKey] = ($depth > 1 && is_iterable($val))
                ? static::keyByRecursive($val, $callable, $overwrite, $depth - 1)
                : $val;
        }

        return $result; /* @phpstan-ignore-line */
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return TValue
     */
    public static function last(iterable $iterable, ?callable $condition = null): mixed
    {
        $result = static::lastOrNull($iterable, $condition);

        if ($result === null) {
            $message = ($condition !== null)
                ? 'Failed to find matching condition.'
                : 'Iterable must contain at least one element.';
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return int|null
     */
    public static function lastIndex(iterable $iterable, callable $condition): ?int
    {
        $copy = static::from($iterable);
        end($copy);

        $count = count($copy);

        while(($key = key($copy)) !== null) {
            $count--;
            $val = current($copy);
            /** @var TKey $key */
            /** @var TValue $val */
            if (static::verify($condition, $key, $val)) {
                return $count;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return TKey|null
     */
    public static function lastKey(iterable $iterable, ?callable $condition = null): mixed
    {
        $copy = static::from($iterable);
        end($copy);

        $condition ??= static fn() => true;

        while(($key = key($copy)) !== null) {
            $val = current($copy);
            /** @var TKey $key */
            /** @var TValue $val */
            if (static::verify($condition, $key, $val)) {
                return $key;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public static function lastOrNull(iterable $iterable, ?callable $condition = null): mixed
    {
        $copy = static::from($iterable);
        end($copy);

        $condition ??= static fn($v, $k) => true;

        while(($key = key($copy)) !== null) {
            /** @var TKey $key */
            /** @var TValue $val */
            $val = current($copy);
            if (static::verify($condition, $key, $val)) {
                return $val;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): TMapValue $callback
     * @return array<TKey, TMapValue>
     */
    public static function map(iterable $iterable, callable $callback): array
    {
        $mapped = [];
        foreach ($iterable as $key => $val) {
            $mapped[$key] = $callback($val, $key);
        }
        return $mapped;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return T
     */
    public static function max(iterable $iterable): mixed
    {
        return max(static::from($iterable)); /** @phpstan-ignore-line */
    }

    /**
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): mixed $callback
     * @return TValue|null
     */
    public static function maxBy(iterable $iterable, callable $callback)
    {
        $maxResult = null;
        $maxValue = null;

        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);

            if ($result === null) {
                throw new RuntimeException('Non-comparable value "null" returned for key:'.$key);
            }

            if ($maxResult === null || $result > $maxResult) {
                $maxResult = $result;
                $maxValue = $val;
            }
        }

        return $maxValue;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * @param iterable<TKey, TValue> $iterable2
     * @return array<TKey, TValue>
     */
    public static function merge(iterable $iterable1, iterable $iterable2): array
    {
        return static::mergeRecursive($iterable1, $iterable2, 1);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * @param iterable<TKey, TValue> $iterable2
     * @param int<1, max> $depth
     * @return array<TKey, TValue>
     */
    public static function mergeRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $merged = static::from($iterable1);
        foreach ($iterable2 as $key => $val) {
            if (is_int($key)) {
                $merged[] = $val;
            } else if ($depth > 1 && array_key_exists($key, $merged) && is_iterable($merged[$key]) && is_iterable($val)) {
                $merged[$key] = static::mergeRecursive($merged[$key], $val, $depth - 1);
            } else {
                $merged[$key] = $val;
            }
        }
        return $merged; /* @phpstan-ignore-line */
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return TValue
     */
    public static function min(iterable $iterable): mixed
    {
        return min(static::from($iterable)); /** @phpstan-ignore-line */
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): mixed $callback
     * @return TValue|null
     */
    public static function minBy(iterable $iterable, callable $callback)
    {
        $minResult = null;
        $minVal = null;

        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);

            if ($result === null) {
                throw new RuntimeException('Non-comparable value "null" returned for key:'.$key);
            }

            if ($minResult === null || $result < $minResult) {
                $minResult = $result;
                $minVal = $val;
            }
        }

        return $minVal;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array{ min: T, max: T }
     */
    public static function minMax(iterable $iterable): array
    {
        $min = null;
        $max = null;
        foreach ($iterable as $val) {
            if ($min === null || $min > $val) {
                $min = $val;
            }
            if ($max === null || $max < $val) {
                $max = $val;
            }
        }

        if ($min === null || $max === null) {
            throw new RuntimeException('Iterable must contain at least one element.');
        }

        return compact('min', 'max');
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param mixed $value
     * @return bool
     */
    public static function notContains(iterable $iterable, mixed $value): bool
    {
        return !static::contains($iterable, $value);
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * @param int|string $key
     * @return bool
     */
    public static function notContainsKey(iterable $iterable, int|string $key): bool
    {
        return !static::containsKey($iterable, $key);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param iterable<TKey> $keys
     * @return array<TKey, TValue>
     */
    public static function only(iterable $iterable, iterable $keys): array
    {
        $copy = static::from($iterable);
        $array = [];
        foreach ($keys as $key) {
            $array[$key] = $copy[$key];
        }
        return $array;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $size
     * @param TValue $value
     * @return array<TKey, TValue>
     */
    public static function pad(iterable $iterable, int $size, mixed $value): array
    {
        return array_pad(static::from($iterable), $size, $value); /** @phpstan-ignore-line */
    }

    /**
     * @template TKey of array-key
     * @param iterable<array-key, mixed> $iterable
     * @param TKey $key
     * @return array<int, mixed>
     */
    public static function pluck(iterable $iterable, int|string $key): array
    {
        return array_column(static::from($iterable), $key);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @return TValue|null
     */
    public static function pop(array &$array): mixed
    {
        return array_pop($array);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param int $amount
     * @return array<TKey, TValue>
     */
    public static function popMany(array &$array, int $amount): array
    {
        Assert::greaterThanEq($amount, 0);
        return array_splice($array, -$amount);
    }

    /**
     * Move elements that match condition to the top of the array.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return array<TKey, TValue>
     */
    public static function prioritize(iterable $iterable, callable $condition): array
    {
        $prioritized = [];
        $remains = [];
        foreach ($iterable as $key => $val) {
            static::verify($condition, $key, $val)
                ? $prioritized[$key] = $val
                : $remains[$key] = $val;
        }
        return static::merge($prioritized, $remains);
    }

    /**
     * @template T
     * @param array<T> $array
     * @param array-key $key
     * @param bool $found
     * @return T|null
     */
    public static function pull(array &$array, int|string $key, bool &$found = null): mixed
    {
        $found = false;
        if (array_key_exists($key, $array)) {
            $found = true;
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }
        return null;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param T ...$value
     * @return void
     */
    public static function push(array &$array, mixed ...$value): void
    {
        foreach ($value as $v) {
            $array[] = $v;
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TValue, TKey): TValue $callback
     * @return TValue
     */
    public static function reduce(iterable $iterable, callable $callback): mixed
    {
        $result = null;
        $initialized = false;
        foreach ($iterable as $key => $val) {
            if (!$initialized) {
                $result = $val;
                $initialized = true;
            } else {
                $result = $callback($result, $val, $key);
            }
        }

        if ($result === null) {
            Assert::minCount([], 1);
        }

        return $result; /** @phpstan-ignore-line */
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, TKey>
     */
    public static function remove(array &$array, mixed $value, ?int $limit = null): array
    {
        $counter = 0;
        $limit ??= PHP_INT_MAX;
        $removed = [];
        foreach ($array as $key => $val) {
            if ($counter < $limit && $val === $value) {
                unset($array[$key]);
                $removed[] = $key;
                ++$counter;
            }
        }
        return $removed;
    }

    /**
     * @param array<mixed> $array
     * @param array-key $key
     * @return bool
     */
    public static function removeKey(array &$array, int|string $key): bool
    {
        $found = false;
        static::pull($array, $key, $found);
        return $found;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int $times
     * @return array<T>
     */
    public static function repeat(iterable $iterable, int $times): array
    {
        Assert::greaterThanEq($times, 0);

        $array = [];
        for ($i = 0; $i < $times; $i++) {
            foreach ($iterable as $val) {
                $array[] = $val;
            }
        }
        return $array;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<TKey, TValue>
     */
    public static function reverse(iterable $iterable): array
    {
        $array = static::from($iterable);
        return array_reverse($array, static::isAssoc($array));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $count
     * @return array<TKey, TValue>
     */
    public static function rotate(iterable $iterable, int $count): array
    {
        $array = static::from($iterable);
        $rotates = array_splice($array, 0, $count);
        return array_merge($array, $rotates);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return TValue
     */
    public static function sample(iterable $iterable): mixed
    {
        $array = static::from($iterable);
        return $array[array_rand($array)];
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $amount
     * @return array<TKey, TValue>
     */
    public static function sampleMany(iterable $iterable, int $amount): array
    {
        $array = static::from($iterable);
        /** @var array<TKey> $sampledKeys */
        $sampledKeys = (array) array_rand($array, $amount);
        return static::only($array, $sampledKeys);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return bool
     */
    public static function satisfyAll(iterable $iterable, callable $condition): bool
    {
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return bool
     */
    public static function satisfyAny(iterable $iterable, callable $condition): bool
    {
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int|string $key
     * @param T $value
     * @return void
     */
    public static function set(array &$array, int|string $key, mixed $value): void
    {
        $array[$key] = $value;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int|string $key
     * @param T $value
     * @param bool &$result
     * @return void
     */
    public static function setIfNotExists(array &$array, int|string $key, mixed $value, bool &$result = null): void
    {
        $result = false;
        if (static::notContainsKey($array, $key)) {
            static::set($array, $key, $value);
            $result = true;
        }
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int|string $key
     * @param T $value
     * @param bool &$result
     * @return void
     */
    public static function setIfExists(array &$array, int|string $key, mixed $value, bool &$result = null): void
    {
        $result = false;
        if (static::containsKey($array, $key)) {
            static::set($array, $key, $value);
            $result = true;
        }
    }

    /**
     * @template TKey
     * @template TValue
     * @param array<TKey, TValue> $array
     * @return TValue|null
     */
    public static function shift(array &$array): mixed
    {
        return array_shift($array);
    }

    /**
     * @template TKey
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param int $amount
     * @return array<int, TValue>
     */
    public static function shiftMany(array &$array, int $amount): array
    {
        Assert::greaterThanEq($amount, 0);
        return array_splice($array, 0, $amount);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<array-key, TValue>
     */
    public static function shuffle(iterable $iterable): array
    {
        $copy = static::from($iterable);
        $size = count($copy);
        $isList = static::isList($copy);
        $array = [];
        while ($size > 0) {
            $key = array_rand($copy);
            $isList
                ? $array[] = $copy[$key]
                : $array[$key] = $copy[$key];
            unset($copy[$key]);
            --$size;
        }
        return $array;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $offset
     * @param int|null $length
     * @return array<TKey, TValue>
     */
    public static function slice(iterable $iterable, int $offset, ?int $length = null): array
    {
        $array = static::from($iterable);
        $preserveKeys = static::isAssoc($array);
        return array_slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool | null $condition
     * @return TValue
     */
    public static function sole(iterable $iterable, ?callable $condition = null): mixed
    {
        $array = ($condition !== null)
            ? static::filter($iterable, $condition)
            : static::from($iterable);

        if (($count = count($array)) !== 1) {
            throw new RuntimeException("Expected only one element in result. $count given.");
        }

        /** @var TValue $current */
        $current = current($array);

        return $current;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $flag
     * @return array<TKey, TValue>
     */
    public static function sort(iterable $iterable, int $flag = SORT_REGULAR): array
    {
        $copy = static::from($iterable);
        asort($copy, $flag);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @return array<TKey, TValue>
     */
    public static function sortBy(iterable $iterable, callable $callback, int $flag = SORT_REGULAR): array
    {
        return static::sortByInternal($iterable, $callback, $flag, true);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @return array<TKey, TValue>
     */
    public static function sortByDesc(iterable $iterable, callable $callback, int $flag = SORT_REGULAR): array
    {
        return static::sortByInternal($iterable, $callback, $flag, false);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $flag
     * @return array<TKey, TValue>
     */
    public static function sortByKey(iterable $iterable, int $flag = SORT_REGULAR): array
    {
        $copy = static::from($iterable);
        ksort($copy, $flag);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $flag
     * @return array<TKey, TValue>
     */
    public static function sortByKeyDesc(iterable $iterable, int $flag = SORT_REGULAR): array
    {
        $copy = static::from($iterable);
        krsort($copy, $flag);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): mixed $callback
     * @param int $flag
     * @param bool $ascending
     * @return array<TKey, TValue>
     */
    protected static function sortByInternal(iterable $iterable, callable $callback, int $flag, bool $ascending): array
    {
        $copy = static::from($iterable);

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

        return $sorted;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $flag
     * @return array<TKey, TValue>
     */
    public static function sortDesc(iterable $iterable, int $flag = SORT_REGULAR): array
    {
        $copy = static::from($iterable);
        arsort($copy, $flag);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TValue): int $comparison
     * @return array<TKey, TValue>
     */
    public static function sortWith(iterable $iterable, callable $comparison): array
    {
        $copy = static::from($iterable);
        uasort($copy, $comparison);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TKey, TKey): int $comparison
     * @return array<TKey, TValue>
     */
    public static function sortWithKey(iterable $iterable, callable $comparison): array
    {
        $copy = static::from($iterable);
        uksort($copy, $comparison);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable
     * @return float|int
     */
    public static function sum(iterable $iterable): float|int
    {
        $total = 0;
        foreach($iterable as $val) {
            $total += $val;
        }
        return $total;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $amount
     * @return array<TKey, TValue>
     */
    public static function take(iterable $iterable, int $amount): array
    {
        return $amount > 0
            ? array_slice(static::from($iterable), 0, $amount)
            : array_slice(static::from($iterable), $amount, -$amount);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return array<TKey, TValue>
     */
    public static function takeUntil(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, $condition) ?? PHP_INT_MAX;
        return static::take($iterable, $index);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $condition
     * @return array<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, static fn($val, $key) => !$condition($val, $key)) ?? PHP_INT_MAX;
        return static::take($iterable, $index);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<array-key, int>
     */
    public static function tally(iterable $iterable): array
    {
        $mapping = [];
        foreach ($iterable as $val) {
            Assert::validArrayKey($val);
            $mapping[$val] ??= 0;
            $mapping[$val]++;
        }
        return $mapping;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param string|null $namespace
     * @return string
     */
    public static function toUrlQuery(iterable $iterable, ?string $namespace = null): string
    {
        $array = static::from($iterable);
        $data = $namespace !== null ? [$namespace => $array] : $array;
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * @param iterable<TKey, TValue> $iterable2
     * @return array<TKey, TValue>
     */
    public static function union(iterable $iterable1, iterable $iterable2): array
    {
        return static::unionRecursive($iterable1, $iterable2, 1);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * @param iterable<TKey, TValue> $iterable2
     * @param int<1, max> $depth
     * @return array<TKey, TValue>
     */
    public static function unionRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $union = static::from($iterable1);
        foreach ($iterable2 as $key => $val) {
            $key = static::ensureKey($key);
            if (is_int($key)) {
                $union[] = $val;
            } else if (!array_key_exists($key, $union)) {
                $union[$key] = $val;
            } else if ($depth > 1 && is_iterable($union[$key]) && is_iterable($val)) {
                $union[$key] = static::unionRecursive($union[$key], $val, $depth - 1);
            }
        }
        return $union; /** @phpstan-ignore-line */
    }

    /**
     * @see uniqueBy for details
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<TKey, TValue>
     */
    public static function unique(iterable $iterable): array
    {
        return static::uniqueBy($iterable, static fn($val) => $val);
    }

    /**
     * Must do custom unique because array_unique does a string conversion before comparing.
     * For example, `[1, true, null, false]` will result in: `[0 => 1, 2 => null]` 🤦
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param callable(TValue, TKey): bool $callback
     * @return array<TKey, TValue>
     */
    public static function uniqueBy(iterable $iterable, callable $callback): array
    {
        $ukeys = [];
        $preserved = [];
        foreach ($iterable as $key => $val) {
            $ukey = $callback($val, $key);
            if (! in_array($ukey, $ukeys, true)) {
                $ukeys[] = $ukey;
                $preserved[$key] = $val;
            }
        }
        return $preserved;
    }

    /**
     * @param array<mixed> $array
     * @param mixed ...$value
     * @return void
     */
    public static function unshift(array &$array, mixed ...$value): void
    {
        for($i = count($value) - 1; $i >= 0; $i--) {
            array_unshift($array, $value[$i]);
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<int, TValue>
     */
    public static function values(iterable $iterable): array
    {
        return array_values(static::from($iterable));
    }

    /**
     * @template T
     * @param T|iterable<array-key, T> $value
     * @return array<array-key, T>
     */
    public static function wrap(mixed $value): array
    {
        if (is_iterable($value)) {
            $value = static::from($value);
        }
        if (is_array($value)) {
            return $value;
        }
        return [$value];
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param array<TKey> $keys
     * @return mixed
     */
    protected static function dig(array $array, array $keys): mixed
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

    /**
     * @template TKey of array-key
     * @template TValue
     * @param callable(TValue, TKey): bool $condition
     * @param TKey $key
     * @param TValue $val
     * @return bool
     */
    protected static function verify(callable $condition, mixed $key, mixed $val): bool
    {
        return $condition($val, $key);
    }

    /**
     * @param mixed $key
     * @return int|string
     */
    protected static function ensureKey(mixed $key): int|string
    {
        if (is_int($key) || is_string($key)) {
            return $key;
        }
        throw new InvalidKeyException($key);
    }
}
