<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidKeyException;
use Kirameki\Exception\InvalidValueException;
use RuntimeException;
use Traversable;
use function array_column;
use function array_is_list;
use function array_key_exists;
use function array_key_last;
use function array_filter;
use function array_pad;
use function array_pop;
use function array_rand;
use function array_reverse;
use function array_shift;
use function array_slice;
use function array_splice;
use function array_sum;
use function array_unshift;
use function array_values;
use function count;
use function current;
use function end;
use function explode;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_callable;
use function is_int;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function key;
use function max;
use function min;
use function next;
use function prev;
use function str_contains;

class Arr
{
    use Concerns\Macroable;

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int $position
     * @return T|null
     */
    public static function at(iterable $iterable, int $position)
    {
        $array = static::from($iterable);
        $offset = $position >= 0 ? $position : count($array) + $position;
        $counter = 0;

        foreach ($array as $item) {
            if ($counter === $offset) {
                return $item;
            }
            $counter+= 1;
        }

        return null;
    }

    /**
     * @param iterable<int|float> $iterable
     * @param bool|null $allowEmpty
     * @return float|int
     */
    public static function average(iterable $iterable, ?bool $allowEmpty = true): float|int
    {
        $array = static::from($iterable);
        $size = count($array);

        if ($size === 0 && $allowEmpty) {
            return 0;
        }

        return array_sum($array) / $size;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return T|null
     */
    public static function coalesce(iterable $iterable): mixed
    {
        foreach ($iterable as $value) {
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return T
     */
    public static function coalesceOrFail(iterable $iterable): mixed
    {
        $result = static::coalesce($iterable);

        if ($result !== null) {
            return $result;
        }

        throw new InvalidValueException('not null', null);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int $depth
     * @return array<T>
     */
    public static function compact(iterable $iterable, int $depth = 1): array
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            if (is_iterable($value) && $depth > 1) {
                $value = static::compact($value, $depth - 1);
            }
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result; /* @phpstan-ignore-line */
    }

    /**
     * @param iterable<mixed> $iterable
     * @param mixed|callable $value
     * @return bool
     */
    public static function contains(iterable $iterable, mixed $value): bool
    {
        $call = is_callable($value) ? $value : static fn($item) => $item === $value;
        foreach ($iterable as $key => $item) {
            if (static::verify($call, $key, $item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param int|string $key
     * @return bool
     */
    public static function containsKey(iterable $iterable, int|string $key): bool
    {
        $array = static::from($iterable);

        if (static::isNotDottedKey($key)) {
            return array_key_exists($key, $array);
        }

        $segments = is_string($key) ? explode('.', $key) : [$key];
        $lastSegment = static::ensureKey(array_pop($segments));
        $ptr = static::dig($array, $segments);
        return is_array($ptr) && array_key_exists($lastSegment, $ptr);
    }

    /**
     * @param iterable<mixed> $iterable
     * @return int
     */
    public static function count(iterable $iterable): int
    {
        $countable = is_countable($iterable) ? $iterable : static::from($iterable);
        return count($countable);
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $condition
     * @return int
     */
    public static function countBy(iterable $iterable, callable $condition): int
    {
        $counter = 0;
        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item)) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int $amount
     * @return array<T>
     */
    public static function drop(iterable $iterable, int $amount): array
    {
        return $amount >= 0
            ? array_slice(static::from($iterable), $amount)
            : array_slice(static::from($iterable), 0, -$amount);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable $condition
     * @return array<T>
     */
    public static function dropUntil(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, $condition) ?? PHP_INT_MAX;
        return static::drop($iterable, $index);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable $condition
     * @return array<T>
     */
    public static function dropWhile(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, static function ($item, $key) use ($condition) {
            return !static::verify($condition, $key, $item);
        }) ?? PHP_INT_MAX;
        return static::drop($iterable, $index);
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $callback
     * @return void
     */
    public static function each(iterable $iterable, callable $callback): void
    {
        foreach ($iterable as $key => $item) {
            $callback($item, $key);
        }
    }

    /**
     * @param iterable<mixed> $iterable
     * @param int $size
     * @param callable $callback
     * @return void
     */
    public static function eachChunk(iterable $iterable, int $size, callable $callback): void
    {
        Assert::positiveInt($size);
        $count = 0;
        $remaining = $size;
        $chunk = [];
        foreach ($iterable as $key => $item) {
            $chunk[$key] = $item;
            $remaining--;
            if ($remaining === 0) {
                $callback($chunk, $count);
                $count += 1;
                $remaining = $size;
                $chunk = [];
            }
        }
        if (!empty($chunk)) {
            $callback($chunk, $count);
        }
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $callback
     * @return void
     */
    public static function eachWithIndex(iterable $iterable, callable $callback): void
    {
        $offset = 0;
        foreach ($iterable as $key => $item) {
            $callback($item, $key, $offset);
            $offset++;
        }
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param array<int|string> $keys
     * @return array<T>
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
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return array<T>
     */
    public static function filter(iterable $iterable, ?callable $condition = null): array
    {
        $condition ??= static fn($item, $key) => !empty($item);
        $values = [];
        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item)) {
                $values[$key] = $item;
            }
        }
        return $values;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return T|null
     */
    public static function first(iterable $iterable, ?callable $condition = null): mixed
    {
        if ($condition === null) {
            foreach ($iterable as $item) {
                return $item;
            }
        }

        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $condition
     * @return int|null
     */
    public static function firstIndex(iterable $iterable, callable $condition): ?int
    {
        $count = 0;
        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item)) {
                return $count;
            }
            $count++;
        }
        return null;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable|null $condition
     * @return int|string|null
     */
    public static function firstKey(iterable $iterable, ?callable $condition = null): int|string|null
    {
        if ($condition === null) {
            foreach ($iterable as $key => $item) {
                return static::ensureKey($key);
            }
        }

        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item)) {
                return static::ensureKey($key);
            }
        }
        return null;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return T
     */
    public static function firstOrFail(iterable $iterable, ?callable $condition = null): mixed
    {
        $result = static::first($iterable, $condition);

        if ($result === null) {
            $message = ($condition !== null)
                ? 'Failed to find matching condition.'
                : 'Iterable must contain at least one item.';
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $callback
     * @return array<mixed>
     */
    public static function flatMap(iterable $iterable, callable $callback): array
    {
        $results = [];
        foreach ($iterable as $value) {
            $result = $callback($value);
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
     * @param iterable<mixed> $iterable
     * @param int $depth
     * @return array<mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): array
    {
        Assert::positiveInt($depth);

        $results = [];
        $func = static function($values, int $depth) use (&$func, &$results) {
            foreach ($values as $value) {
                if (is_iterable($value) && $depth > 0) {
                    $func($value, $depth - 1);
                } else {
                    $results[] = $value;
                }
            }
        };
        $func($iterable, $depth);
        return $results;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param bool $overwrite
     * @return array<int|string, mixed>
     */
    public static function flip(iterable $iterable, bool $overwrite = false): array
    {
        $flipped = [];
        foreach ($iterable as $key => $value) {
            $value = static::ensureKey($value);
            if (!$overwrite && array_key_exists($value, $flipped)) {
                throw new DuplicateKeyException($value, $key);
            }
            $flipped[$value] = $key;
        }
        return $flipped;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param T $initial
     * @param callable $callback
     * @return T
     */
    public static function fold(iterable $iterable, mixed $initial, callable $callback): mixed
    {
        $result = $initial;
        foreach ($iterable as $key => $item) {
            $result = $callback($result, $item, $key);
        }
        return $result;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function from(iterable $iterable): array
    {
        return ($iterable instanceof Traversable)
            ? iterator_to_array($iterable)
            : $iterable;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int|string $key
     * @return T|mixed
     */
    public static function get(iterable $iterable, int|string $key): mixed
    {
        $keys = static::isDottedKey($key) ? explode('.', (string) $key) : [$key];
        return static::dig(static::from($iterable), $keys);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param string|callable $key
     * @return array<array<T>>
     */
    public static function groupBy(iterable $iterable, string|callable $key): array
    {
        $callable = is_string($key) ? static::createDigger($key) : $key;

        $map = [];
        foreach ($iterable as $k => $item) {
            $groupKey = $callable($item, $k);
            if ($groupKey !== null) {
                Assert::validKey($groupKey);
                $map[$groupKey] ??= [];
                $map[$groupKey][] = $item;
            }
        }

        return $map;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public static function implode(iterable $iterable, string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return $prefix.implode($glue, static::from($iterable)).$suffix;
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
     * @param iterable<mixed> $iterable
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
        foreach ($iterable as $_) {
            return false;
        }
        return true;
    }

    /**
     * @param iterable<mixed> $iterable
     * @return bool
     */
    public static function isList(iterable $iterable): bool
    {
        return array_is_list(is_array($iterable) ? $iterable : static::from($iterable));
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
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return array<TKey>
     */
    public static function keys(iterable $iterable): array
    {
        $keys = [];
        while(($key = key($iterable)) !== null) {
            $keys[] = $key;
            next($iterable);
        }
        return $keys;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param string|callable $key
     * @param bool $overwrite
     * @return array<T>
     */
    public static function keyBy(iterable $iterable, string|callable $key, bool $overwrite = false): array
    {
        return static::keyByRecursive($iterable, $key, $overwrite, 1);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param string|callable $key
     * @param bool $overwrite
     * @param int $depth
     * @return array<T>
     */
    public static function keyByRecursive(iterable $iterable, string|callable $key, bool $overwrite = false, int $depth = PHP_INT_MAX): array
    {
        $callable = is_string($key) ? static::createDigger($key) : $key;

        $result = [];
        foreach ($iterable as $oldKey => $item) {
            $newKey = static::ensureKey($callable($item, $oldKey));

            if (!$overwrite && array_key_exists($newKey, $result)) {
                throw new DuplicateKeyException($newKey, $item);
            }

            $result[$newKey] = ($depth > 1 && is_iterable($item))
                ? static::keyByRecursive($item, $callable, $overwrite, $depth - 1)
                : $item;
        }

        return $result; /* @phpstan-ignore-line */
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return T|null
     */
    public static function last(iterable $iterable, ?callable $condition = null): mixed
    {
        $copy = static::from($iterable);
        end($copy);

        $condition ??= static fn($v, $k) => true;

        while(($key = key($copy)) !== null) {
            $item = current($copy);
            if (static::verify($condition, $key, $item)) {
                return $item; /* @phpstan-ignore-line */
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $condition
     * @return int|null
     */
    public static function lastIndex(iterable $iterable, callable $condition): ?int
    {
        $copy = static::from($iterable);
        end($copy);

        $count = count($copy);

        while(($key = key($copy)) !== null) {
            $count--;
            $item = current($copy);
            if (static::verify($condition, $key, $item)) {
                return $count;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return int|string|null
     */
    public static function lastKey(iterable $iterable, ?callable $condition = null): int|string|null
    {
        $copy = static::from($iterable);
        end($copy);

        if ($condition === null) {
            return key($copy);
        }

        while(($key = key($copy)) !== null) {
            $item = current($copy);
            if (static::verify($condition, $key, $item)) {
                return $key;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return T
     */
    public static function lastOrFail(iterable $iterable, ?callable $condition = null): mixed
    {
        $result = static::last($iterable, $condition);

        if ($result === null) {
            $message = ($condition !== null)
                ? 'Failed to find matching condition.'
                : 'Iterable must contain at least one item.';
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @template TKey
     * @param iterable<TKey, mixed> $iterable
     * @param callable $callback
     * @return array<TKey, mixed>
     */
    public static function map(iterable $iterable, callable $callback): array
    {
        $values = [];
        foreach ($iterable as $key => $item) {
            $values[$key] = $callback($item, $key);
        }
        return $values;
    }

    /**
     * @param iterable<bool|float|int> $iterable
     * @return bool|float|int
     */
    public static function max(iterable $iterable): mixed
    {
        return max(static::from($iterable));
    }

    /**
     * @template T1
     * @template T2
     * @param iterable<T1> $iterable1
     * @param iterable<T2> $iterable2
     * @return array<T1|T2>
     */
    public static function merge(iterable $iterable1, iterable $iterable2): array
    {
        return static::mergeRecursive($iterable1, $iterable2, 1);
    }

    /**
     * @template T1
     * @template T2
     * @param iterable<T1> $iterable1
     * @param iterable<T2> $iterable2
     * @param int $depth
     * @return array<T1|T2>
     */
    public static function mergeRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $merged = static::from($iterable1);
        foreach ($iterable2 as $key => $value) {
            $key = static::ensureKey($key);
            if (is_int($key)) {
                $merged[] = $value;
            } else if ($depth > 1 && array_key_exists($key, $merged) && is_iterable($merged[$key]) && is_iterable($value)) {
                $merged[$key] = static::mergeRecursive($merged[$key], $value, $depth - 1);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged; /* @phpstan-ignore-line */
    }

    /**
     * @param iterable<bool|float|int> $iterable
     * @return bool|float|int
     */
    public static function min(iterable $iterable): mixed
    {
        return min(static::from($iterable));
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function minMax(iterable $iterable): array
    {
        $min = null;
        $max = null;
        $containsValues = false;
        foreach ($iterable as $value) {
            $containsValues = true;
            if ($min === null || $min > $value) {
                $min = $value;
            }
            if ($max === null || $max < $value) {
                $max = $value;
            }
        }

        if (!$containsValues) {
            throw new RuntimeException('Iterable must contain at least one item.');
        }

        return [$min, $max]; /** @phpstan-ignore-line */
    }

    /**
     * @param iterable<mixed> $iterable
     * @param mixed|callable $value
     * @return bool
     */
    public static function notContains(iterable $iterable, mixed $value): bool
    {
        return !static::contains($iterable, $value);
    }

    /**
     * @param iterable<mixed> $iterable
     * @param int|string $key
     * @return bool
     */
    public static function notContainsKey(iterable $iterable, int|string $key): bool
    {
        return !static::containsKey($iterable, $key);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param iterable<int|string> $keys
     * @return array<T>
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
     * @template T1
     * @template T2
     * @param iterable<T1> $iterable
     * @param int $size
     * @param T2 $value
     * @return array<T1|T2>
     */
    public static function pad(iterable $iterable, int $size, mixed $value): array
    {
        return array_pad(static::from($iterable), $size, $value);
    }

    /**
     * @param iterable<int|string, mixed> $iterable
     * @param int|string $key
     * @return array<int, mixed>
     */
    public static function pluck(iterable $iterable, int|string $key): array
    {
        if (static::isNotDottedKey($key)) {
            return array_column(static::from($iterable), $key);
        }

        $plucked = [];
        $segments = is_string($key) ? explode('.', $key) : [$key];
        foreach ($iterable as $values) {
            $plucked[] = static::dig($values, $segments); /* @phpstan-ignore-line */
        }
        return $plucked;
    }

    /**
     * @template T
     * @param array<T> $array
     * @return T|null
     */
    public static function pop(array &$array): mixed
    {
        return array_pop($array);
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int $amount
     * @return array<int, T>
     */
    public static function popMany(array &$array, int $amount): array
    {
        Assert::greaterThanOrEqualTo(0, $amount);
        return array_splice($array, -$amount);
    }

    /**
     * Move items that match condition to the top of the array.
     *
     * @template T
     * @param iterable<T> $iterable
     * @param callable $condition
     * @return array<T>
     */
    public static function prioritize(iterable $iterable, callable $condition): array
    {
        $prioritized = [];
        $remains = [];
        foreach ($iterable as $key => $value) {
            static::verify($condition, $key, $value)
                ? $prioritized[$key] = $value
                : $remains[$key] = $value;
        }
        return static::merge($prioritized, $remains);
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int|string $key
     * @param bool &$found
     * @return T|mixed
     */
    public static function pull(array &$array, int|string $key, bool &$found = null): mixed
    {
        $found = false;

        if (static::isNotDottedKey($key)) {
            if (array_key_exists($key, $array)) {
                $found = true;
                return static::pullInternal($array, $key);
            }
            return null;
        }

        $arrayRef = &$array;
        $segments = is_string($key) ? explode('.', $key) : [$key];
        $lastSegment = static::ensureKey(array_pop($segments));
        foreach ($segments as $segment) {
            if (is_array($arrayRef) && array_key_exists($segment, $arrayRef)) {
                $arrayRef = &$arrayRef[$segment];
            } else {
                return null;
            }
        }

        if (array_key_exists($lastSegment, $arrayRef)) {
            $found = true;
            return static::pullInternal($arrayRef, $lastSegment);
        }

        return null;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int|string $key
     * @return T
     */
    protected static function pullInternal(array &$array, int|string $key)
    {
        $value = $array[$key];
        unset($array[$key]);
        return $value;
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
     * @template T
     * @param iterable<T> $iterable
     * @param callable $callback
     * @return T
     */
    public static function reduce(iterable $iterable, callable $callback): mixed
    {
        Assert::iterableHasAtleastOneItem($iterable);

        $reduceable = static::drop($iterable, 1);
        $intial = static::firstOrFail($iterable);

        return static::fold($reduceable, $intial, $callback);
    }

    /**
     * @template T
     * @param array<T> $array
     * @param T $value
     * @param int|null $limit
     * @return array<mixed>
     */
    public static function remove(array &$array, mixed $value, ?int $limit = null): array
    {
        $counter = 0;
        $limit ??= PHP_INT_MAX;
        $removed = [];
        foreach ($array as $key => $item) {
            if ($counter < $limit && $item === $value) {
                unset($array[$key]);
                $removed[] = $key;
                ++$counter;
            }
        }
        return $removed;
    }

    /**
     * @param array<mixed> $array
     * @param int|string $key
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
        Assert::greaterThanOrEqualTo(0, $times);

        $array = [];
        for ($i = 0; $i < $times; $i++) {
            foreach ($iterable as $value) {
                $array[] = $value;
            }
        }
        return $array;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function reverse(iterable $iterable): array
    {
        $array = static::from($iterable);
        return array_reverse($array, static::isAssoc($array));
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return T
     */
    public static function sample(iterable $iterable): mixed
    {
        $arr = static::from($iterable);
        return $arr[array_rand($arr)];
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int $amount
     * @return array<T>
     */
    public static function sampleMany(iterable $iterable, int $amount): array
    {
        $array = static::from($iterable);
        $sampledKeys = (array) array_rand($array, $amount);
        return static::only($array, $sampledKeys);
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $condition
     * @return bool
     */
    public static function satisfyAll(iterable $iterable, callable $condition): bool
    {
        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param callable $condition
     * @return bool
     */
    public static function satisfyAny(iterable $iterable, callable $condition): bool
    {
        return static::contains($iterable, $condition);
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int|string $key
     * @param T $value
     */
    public static function set(array &$array, int|string $key, mixed $value): void
    {
        if (static::isNotDottedKey($key)) {
            $array[$key] = $value;
            return;
        }

        $ptr = &$array;
        $segments = is_string($key) ? explode('.', $key) : [$key];
        $lastSegment = array_pop($segments);
        foreach ($segments as $segment) {
            $ptr[$segment] ??= [];
            $ptr = &$ptr[$segment];
        }
        $ptr[$lastSegment] = $value;
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
     * @template T
     * @param array<T> $array
     * @return T|null
     */
    public static function shift(array &$array): mixed
    {
        return array_shift($array);
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int $amount
     * @return array<int, T>
     */
    public static function shiftMany(array &$array, int $amount): array
    {
        Assert::greaterThanOrEqualTo(0, $amount);
        return array_splice($array, $amount);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function shuffle(iterable $iterable): array
    {
        $copy = static::from($iterable);
        $isList = static::isList($copy);
        $array = [];
        while (!empty($copy)) {
            $key = array_rand($copy);
            $isList
                ? $array[] = $copy[$key]
                : $array[$key] = $copy[$key];
            unset($copy[$key]);
        }
        return $array;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return T
     */
    public static function sole(iterable $iterable, ?callable $condition = null): mixed
    {
        $array = static::from($iterable);

        if ($condition !== null) {
            $array = array_filter($array, $condition);
        }

        if (($count = count($array)) !== 1) {
            throw new RuntimeException("Expected only one item in result. $count given.");
        }

        return current($array);
    }

    /**
     * @param iterable<float|int> $iterable
     * @return float|int
     */
    public static function sum(iterable $iterable): float|int
    {
        $total = 0;
        foreach(static::from($iterable) as $num) {
            $total += $num;
        }
        return $total;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param int $amount
     * @return array<T>
     */
    public static function take(iterable $iterable, int $amount): array
    {
        return $amount > 0
            ? array_slice(static::from($iterable), 0, $amount)
            : array_slice(static::from($iterable), $amount, -$amount);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable $condition
     * @return array<T>
     */
    public static function takeUntil(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, $condition) ?? PHP_INT_MAX;
        return static::take($iterable, $index);
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable $condition
     * @return array<T>
     */
    public static function takeWhile(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, static fn($item, $key) => !$condition($item, $key)) ?? PHP_INT_MAX;
        return static::take($iterable, $index);
    }

    /**
     * @template T of int|string
     * @param iterable<T> $iterable
     * @return array<T, int>
     */
    public static function tally(iterable $iterable): array
    {
        $mapping = [];
        foreach ($iterable as $item) {
            Assert::validKey($item);
            $mapping[$item] ??= 0;
            $mapping[$item]++;
        }
        return $mapping;
    }

    /**
     * @param iterable<mixed> $iterable
     * @param string|null $namespace
     * @return string
     */
    public static function toUrlQuery(iterable $iterable, ?string $namespace = null): string
    {
        $arr = static::from($iterable);
        $data = $namespace !== null ? [$namespace => $arr] : $arr;
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @template T1
     * @template T2
     * @param iterable<T1> $iterable1
     * @param iterable<T2> $iterable2
     * @return array<T1|T2>
     */
    public static function union(iterable $iterable1, iterable $iterable2): array
    {
        return static::unionRecursive($iterable1, $iterable2, 1);
    }

    /**
     * @template T1
     * @template T2
     * @param iterable<T1> $iterable1
     * @param iterable<T2> $iterable2
     * @param int $depth
     * @return array<T1|T2>
     */
    public static function unionRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $union = static::from($iterable1);
        foreach ($iterable2 as $key => $value) {
            $key = static::ensureKey($key);
            if (is_int($key)) {
                $union[] = $value;
            } else if (!array_key_exists($key, $union)) {
                $union[$key] = $value;
            } else if ($depth > 1 && is_iterable($union[$key]) && is_iterable($value)) {
                $union[$key] = static::unionRecursive($union[$key], $value, $depth - 1);
            }
        }
        return $union; /** @phpstan-ignore-line */
    }

    /**
     * @see uniqueBy for details
     *
     * @template T
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function unique(iterable $iterable): array
    {
        return static::uniqueBy($iterable, static fn($value) => $value);
    }

    /**
     * Must do custom unique because array_unique does a string convertion before comparing.
     * For example, `[1, true, null, false]` will result in: `[0 => 1, 2 => null]` 🤦
     *
     * @template T
     * @param iterable<T> $iterable
     * @return array<T>
     */
    public static function uniqueBy(iterable $iterable, callable $callback): array
    {
        $ukeys = [];
        $preserved = [];
        foreach (static::from($iterable) as $key => $value) {
            $ukey = $callback($value, $key);
            if (! in_array($ukey, $ukeys, true)) {
                $ukeys[] = $ukey;
                $preserved[] = [$key, $value];
            }
        }
        $results = [];
        foreach ($preserved as $data) {
            $results[$data[0]] = $data[1];
        }
        return $results;
    }

    /**
     * @param array<mixed> $array
     * @param mixed ...$values
     * @return void
     */
    public static function unshift(array &$array, mixed ...$values): void
    {
        for($i = count($values) - 1; $i >= 0; $i--) {
            array_unshift($array, $values[$i]);
        }
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @return array<int, T>
     */
    public static function values(iterable $iterable): array
    {
        return array_values(static::from($iterable));
    }

    /**
     * @template T
     * @param T|iterable<T> $value
     * @return array<T>
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
     * @template T
     * @param array<T> $array
     * @param array<int|string> $keys
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
     * @private
     * @param string $key
     * @return Closure
     */
    protected static function createDigger(string $key): Closure
    {
        $segments = explode('.', $key);
        return static fn($v, $k) => static::dig($v, $segments);
    }

    /**
     * @param callable $condition
     * @param mixed $key
     * @param mixed $item
     * @return bool
     */
    protected static function verify(callable $condition, mixed $key, mixed $item): bool
    {
        $result = $condition($item, $key);
        if (is_bool($result)) {
            return $result;
        }
        throw new InvalidValueException('bool', $result);
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
