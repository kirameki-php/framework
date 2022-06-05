<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidKeyException;
use Kirameki\Support\Str;
use LogicException;
use RuntimeException;
use Webmozart\Assert\Assert;
use function abs;
use function array_column;
use function array_diff;
use function array_diff_key;
use function array_intersect;
use function array_intersect_key;
use function array_is_list;
use function array_key_exists;
use function array_map;
use function array_pop;
use function array_push;
use function array_rand;
use function array_reverse;
use function array_shift;
use function array_splice;
use function array_unshift;
use function arsort;
use function asort;
use function count;
use function current;
use function end;
use function get_resource_id;
use function http_build_query;
use function is_array;
use function is_bool;
use function is_countable;
use function is_float;
use function is_int;
use function is_iterable;
use function is_null;
use function is_object;
use function is_resource;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function key;
use function krsort;
use function ksort;
use function prev;
use function spl_object_id;
use function uasort;
use function uksort;

class Arr
{
    /**
     * `$array = [1, 2]; Arr::append($array, 3); // [1, 2, 3]`
     *
     * `$array = [1, 2]; Arr::append($array, 3, 4); // [1, 2, 3, 4]`
     *
     * `$array = ['a' => 1]; Arr::append($array, 1); // ['a' => 1, 0 => 2]`
     *
     * @template T
     * @param array<T> &$array Array reference which the value is getting appended.
     * @param T ...$value Value(s) to be appended to the array.
     * @return void
     */
    public static function append(array &$array, mixed ...$value): void
    {
        array_push($array, ...$value);
    }

    /**
     * `Arr::at([6, 7], 1); // 7`
     *
     * `Arr::at([6, 7], -1); // 7`
     *
     * `Arr::at(['a' => 1, 'b' => 2], 0); // 1`
     *
     * `Arr::at([6], 1); // null
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $position Position of array starting with 0. Negative position will traverse from tail.
     * @return TValue|null
     */
    public static function at(iterable $iterable, int $position): mixed
    {
        return static::atOr($iterable, $position, null);
    }

    /**
     * `Arr::at([6, 7], 1); // 7`
     *
     * `Arr::at([6, 7], -1); // 7`
     *
     * `Arr::at(['a' => 1, 'b' => 2], 0); // 1`
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $position Position of array starting with 0. Negative position will traverse from tail.
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public static function atOr(iterable $iterable, int $position, mixed $default): mixed
    {
        $array = static::from($iterable);
        $offset = $position >= 0 ? $position : count($array) + $position;
        $count = 0;

        foreach ($array as $val) {
            if ($count === $offset) {
                return $val;
            }
            ++$count;
        }

        return $default;
    }

    /**
     * `Arr::at([6, 7], 1); // 7`
     *
     * `Arr::at([6, 7], -1); // 7`
     *
     * `Arr::at(['a' => 1, 'b' => 2], 0); // 1`
     *
     * `Arr::at([6], 1); // InvalidValueException`
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $position Position of array starting with 0. Negative position will traverse from tail.
     * @return TValue
     */
    public static function atOrFail(iterable $iterable, int $position): mixed
    {
        $miss = Miss::instance();

        $result = static::atOr($iterable, $position, $miss);

        if ($result instanceof Miss) {
            throw new RuntimeException("Index out of bounds. position: $position");
        }

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param bool $allowEmpty Allow iterable to be empty. In which case it will return 0.
     * @return float|int
     */
    public static function average(iterable $iterable, bool $allowEmpty = true): float|int
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $size
     * @param bool|null $reindex
     * @return array<int, array<TKey, TValue>>
     */
    public static function chunk(iterable $iterable, int $size, ?bool $reindex = null): array
    {
        return iterator_to_array(Iter::chunk($iterable, $size, $reindex));
    }

    /**
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return TValue|null
     */
    public static function coalesce(iterable $iterable): mixed
    {
        foreach ($iterable as $val) {
            if ($val !== null) {
                return $val;
            }
        }
        return null;
    }

    /**
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return TValue
     */
    public static function coalesceOrFail(iterable $iterable): mixed
    {
        $result = static::coalesce($iterable);

        if ($result === null) {
            throw new RuntimeException('Non-null value could not be found.');
        }

        return $result;
    }

    /**
     * `Arr::compact([null, 0, false]); // [0, false]`
     *
     * `Arr::compact([[null]]); // [[null]]` Doesn't remove inner null since default depth is 1.
     *
     * `Arr::compact([[null]], 2); // [[]]` Removes inner null since depth is set to 2.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $depth Optional. Must be >= 1. Default is 1.
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function compact(iterable $iterable, int $depth = 1, ?bool $reindex = null): array
    {
        $result = [];
        foreach (Iter::compact($iterable, $reindex) as $key => $val) {
            if (is_iterable($val) && $depth > 1) {
                /** @var TValue $val */
                $val = static::compact($val, $depth - 1, $reindex); /** @phpstan-ignore-line */
            }
            $result[$key] = $val;
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @param int|string $key
     * @return bool
     */
    public static function containsKey(iterable $iterable, int|string $key): bool
    {
        return array_key_exists($key, static::from($iterable));
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @return int
     */
    public static function count(iterable $iterable): int
    {
        if (is_countable($iterable)) {
            return count($iterable);
        }
        $count = 0;
        foreach ($iterable as $_) {
            ++$count;
        }
        return $count;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return int
     */
    public static function countBy(iterable $iterable, Closure $condition): int
    {
        $count = 0;
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                ++$count;
            }
        }
        return $count;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param iterable<TKey, TValue> $items
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function diff(iterable $iterable, iterable $items, ?bool $reindex = null): array
    {
        $array = static::from($iterable);

        $reindex ??= array_is_list($array);

        $result = array_diff($array, static::from($items));

        if ($reindex) {
            static::reindex($result);
        }

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $amount
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function drop(iterable $iterable, int $amount, ?bool $reindex = null): array
    {
        return iterator_to_array(Iter::drop($iterable, $amount, $reindex));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function dropUntil(iterable $iterable, Closure $condition, ?bool $reindex = null): array
    {
        return iterator_to_array(Iter::dropUntil($iterable, $condition, $reindex));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, Closure $condition, ?bool $reindex = null): array
    {
        return iterator_to_array(Iter::dropWhile($iterable, $condition, $reindex));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return array<TKey, TValue>
     */
    public static function duplicates(iterable $iterable): array
    {
        $array = [];
        $refs = [];
        foreach ($iterable as $key => $val) {
            $ref = static::valueToKey($val);
            $refs[$ref][] = $key;
            $array[$key] = $val;
        }

        $duplicates = [];
        foreach ($refs as $keys) {
            if (count($keys) > 1) {
                $duplicates[] = $array[$keys[0]];
            }
        }

        return $duplicates;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): void $callback
     * @return void
     */
    public static function each(iterable $iterable, Closure $callback): void
    {
        foreach ($iterable as $key => $val) {
            $callback($val, $key);
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param array<int|string> $keys
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function except(iterable $iterable, iterable $keys, ?bool $reindex = null): array
    {
        $copy = static::from($iterable);
        $reindex ??= array_is_list($copy);

        foreach ($keys as $key) {
            unset($copy[$key]);
        }

        if ($reindex) {
            static::reindex($copy);
        }

        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function filter(iterable $iterable, Closure $condition, ?bool $reindex = null): array
    {
        return iterator_to_array(Iter::filter($iterable, $condition, $reindex));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public static function first(iterable $iterable, ?Closure $condition = null): mixed
    {
        return static::firstOr($iterable, null, $condition);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return int|null
     */
    public static function firstIndex(iterable $iterable, Closure $condition): ?int
    {
        $count = 0;
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                return $count;
            }
            ++$count;
        }
        return null;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public static function firstKey(iterable $iterable, ?Closure $condition = null): int|string|null
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
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public static function firstOr(iterable $iterable, mixed $default, ?Closure $condition = null): mixed
    {
        $condition ??= static fn() => true;

        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                return $val;
            }
        }

        return $default;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public static function firstOrFail(iterable $iterable, ?Closure $condition = null): mixed
    {
        $miss = Miss::instance();

        $result = static::firstOr($iterable, $miss, $condition);

        if ($result instanceof Miss) {
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @return array<int, mixed>
     */
    public static function flatMap(iterable $iterable, Closure $callback): array
    {
        return iterator_to_array(Iter::flatMap($iterable, $callback));
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @param int $depth Depth must be >= 1. Default: 1.
     * @return array<int, mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): array
    {
        $result = [];
        foreach (Iter::flatten($iterable, $depth) as $val) {
            $result[] = $val;
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param bool $overwrite
     * @return array<array-key, TKey>
     */
    public static function flip(iterable $iterable, bool $overwrite = false): array
    {
        $flipped = [];
        foreach (Iter::flip($iterable) as $key => $val) {
            if (is_int($key) || is_string($key)) {
                if (!$overwrite && array_key_exists($key, $flipped)) {
                    throw new DuplicateKeyException($key, $val);
                }
                $flipped[$key] = $val;
            } else {
                throw new InvalidKeyException($key);
            }
        }
        return $flipped;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @template U
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param U $initial
     * @param Closure(U, TValue, TKey): U $callback
     * @return U
     */
    public static function fold(iterable $iterable, mixed $initial, Closure $callback): mixed
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return array<TKey, TValue>
     */
    public static function from(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }
        return iterator_to_array($iterable);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int|string $key
     * @return TValue|null
     */
    public static function get(iterable $iterable, int|string $key): mixed
    {
        return static::getOr($iterable, $key, null);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int|string $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public static function getOr(iterable $iterable, int|string $key, mixed $default): mixed
    {
        return static::from($iterable)[$key] ?? $default;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int|string $key
     * @return TValue
     */
    public static function getOrFail(iterable $iterable, int|string $key)
    {
        $miss = Miss::instance();
        $result = static::getOr($iterable, $key, $miss);

        if ($result instanceof Miss) {
            throw new RuntimeException("Undefined array key $key");
        }

        return $result;
    }

    /**
     * @template TGroupKey as array-key
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param TGroupKey|Closure(TValue, TKey): TGroupKey $key
     * @param bool|null $reindex
     * @return array<TGroupKey, array<int|TKey, TValue>>
     */
    public static function groupBy(iterable $iterable, int|string|Closure $key, ?bool $reindex = null): array
    {
        $callback = (is_string($key) || is_int($key))
            ? static fn(array $val, $_key) => $val[$key]
            : $key;

        $map = [];
        foreach ($iterable as $_key => $val) {
            $reindex ??= $_key === 0;
            /** @var TGroupKey $groupKey */
            $groupKey = $callback($val, $_key);
            if ($groupKey !== null) {
                Assert::validArrayKey($groupKey);
                $map[$groupKey] ??= [];
                $reindex
                    ? $map[$groupKey][] = $val
                    : $map[$groupKey][$_key] = $val;
            }
        }

        return $map;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param int $index
     * @param T $value
     * @param bool|null $reindex
     * @return void
     */
    public static function insertAt(array &$array, int $index, mixed $value, ?bool $reindex = null): void
    {
        static::insertManyAt($array, $index, [$value], $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param int $index
     * @param array<TKey, TValue> $value
     * @param bool|null $reindex
     * @return void
     */
    public static function insertManyAt(array &$array, int $index, array $value, ?bool $reindex = null): void
    {
        // NOTE: This used to be simply array_splice($array, $index, 0, $value) but passing replacement
        // in the 4th argument does not preserve keys so implementation was changed to the current one.

        // Offset is off by one for negative indexes (Ex: -2 inserts at 3rd element from right).
        // So we add one to correct offset. If adding to one results in 0, we set it to max count
        // to put it at the end.
        if ($index < 0) {
            $index = $index === -1 ? count($array) : $index + 1;
        }

        $tail = array_splice($array, $index);
        $reindex ??= array_is_list($array);

        foreach ($value as $key => $val) {
            $reindex
                ? $array[] = $val
                : $array[$key] = $val;
        }

        foreach ($tail as $key => $val) {
            if ($reindex) {
                $array[] = $val;
            } elseif (!array_key_exists($key, $array)) {
                $array[$key] = $val;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param iterable<TKey, TValue> $items
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function intersect(iterable $iterable, iterable $items, ?bool $reindex = null): array
    {
        $array = static::from($iterable);

        $reindex ??= array_is_list($array);

        $result = array_intersect($array, static::from($items));

        if ($reindex) {
            static::reindex($result);
        }

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<array-key, mixed> $iterable Iterable to be traversed.
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
     * @param iterable<array-key, mixed> $iterable Iterable to be traversed.
     * @return bool
     */
    public static function isList(iterable $iterable): bool
    {
        return array_is_list(static::from($iterable));
    }

    /**
     * @param iterable<array-key, mixed> $iterable Iterable to be traversed.
     * @return bool
     */
    public static function isNotEmpty(iterable $iterable): bool
    {
        return !static::isEmpty($iterable);
    }

    /**
     * @param iterable<array-key, mixed> $iterable Iterable to be traversed.
     * @param string $glue
     * @param string|null $prefix
     * @param string|null $suffix
     * @return string
     */
    public static function join(iterable $iterable, string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        $str = null;
        foreach ($iterable as $value) {
            $str.= $str !== null
                ? $glue . $value
                : $value;
        }
        return $prefix . $str . $suffix;
    }

    /**
     * @template T
     * @param iterable<array-key, T> $iterable Iterable to be traversed.
     * @param string|Closure(T, mixed): array-key $key
     * @param bool $overwrite
     * @return array<array-key, T>
     */
    public static function keyBy(iterable $iterable, string|Closure $key, bool $overwrite = false): array
    {
        $callback = is_string($key)
            ? static fn (): string => $key
            : $key;

        $result = [];
        foreach ($iterable as $oldKey => $val) {
            $newKey = static::ensureKey($callback($val, $oldKey));

            if (!$overwrite && array_key_exists($newKey, $result)) {
                throw new DuplicateKeyException($newKey, $val);
            }

            $result[$newKey] = $val;
        }
        /** @var array<T> $result */
        return $result;
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @return array<int, TKey>
     */
    public static function keys(iterable $iterable): array
    {
        return iterator_to_array(Iter::keys($iterable));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue|null
     */
    public static function last(iterable $iterable, ?Closure $condition = null): mixed
    {
        return static::lastOr($iterable, null, $condition);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return int|null
     */
    public static function lastIndex(iterable $iterable, Closure $condition): ?int
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TKey|null
     */
    public static function lastKey(iterable $iterable, ?Closure $condition = null): mixed
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
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public static function lastOr(iterable $iterable, mixed $default, ?Closure $condition = null): mixed
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

        return $default;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public static function lastOrFail(iterable $iterable, ?Closure $condition = null): mixed
    {
        $miss = Miss::instance();

        $result = static::lastOr($iterable, $miss, $condition);

        if ($result instanceof Miss) {
            $message = ($condition !== null)
                ? 'Failed to find matching condition.'
                : 'Iterable must contain at least one element.';
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @template TKey
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return array<TKey, TMapValue>
     */
    public static function map(iterable $iterable, Closure $callback): array
    {
        return iterator_to_array(Iter::map($iterable, $callback));
    }

    /**
     * @template T
     * @param iterable<array-key, T> $iterable Iterable to be traversed.
     * @return T
     */
    public static function max(iterable $iterable): mixed
    {
        $result = static::maxBy($iterable, static fn(mixed $val): mixed => $val);
        if ($result === null) {
            throw new RuntimeException('$iterable must contain at least one value');
        }
        return $result;
    }

    /**
     * @template TKey
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @return TValue
     */
    public static function maxBy(iterable $iterable, Closure $callback)
    {
        $maxResult = null;
        $maxValue = null;

        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);

            if ($result === null) {
                throw new RuntimeException("Non-comparable value \"null\" returned for key: $key");
            }

            if ($maxResult === null || $result > $maxResult) {
                $maxResult = $result;
                $maxValue = $val;
            }
        }

        if ($maxValue === null) {
            throw new RuntimeException('$iterable must contain at least one value');
        }

        return $maxValue;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1 Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function merge(iterable $iterable1, iterable $iterable2, ?bool $reindex = null): array
    {
        return static::mergeRecursive($iterable1, $iterable2, 1, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1 Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * @param int<1, max> $depth
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function mergeRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX, ?bool $reindex = null): array
    {
        $merged = static::from($iterable1);
        $reindex = array_is_list($merged);
        foreach ($iterable2 as $key => $val) {
            if ($reindex) {
                $merged[] = $val;
            } else if ($depth > 1 && array_key_exists($key, $merged) && is_iterable($merged[$key]) && is_iterable($val)) {
                $merged[$key] = static::mergeRecursive($merged[$key], $val, $depth - 1);
            } else {
                $merged[$key] = $val;
            }
        }
        /** @var array<TKey, TValue> $merged */
        return $merged;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @return TValue
     */
    public static function min(iterable $iterable): mixed
    {
        $result = static::minBy($iterable, static fn(mixed $val): mixed => $val);
        if ($result === null) {
            throw new RuntimeException('$iterable must contain at least one value');
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @return TValue|null
     */
    public static function minBy(iterable $iterable, Closure $callback)
    {
        $minResult = null;
        $minVal = null;

        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);

            if ($result === null) {
                throw new RuntimeException("Non-comparable value \"null\" returned for key: $key");
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
     * @param iterable<array-key, T> $iterable Iterable to be traversed.
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

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param mixed $value
     * @return bool
     */
    public static function notContains(iterable $iterable, mixed $value): bool
    {
        return !static::contains($iterable, $value);
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param iterable<TKey> $keys
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function only(iterable $iterable, iterable $keys, ?bool $reindex = null): array
    {
        $copy = static::from($iterable);
        $reindex ??= array_is_list($copy);

        $array = [];
        foreach ($keys as $key) {
            $reindex
                ? $array[] = $copy[$key]
                : $array[$key] = $copy[$key];
        }

        return $array;
    }

    /**
     * @template TValue
     * @param iterable<int, TValue> $iterable Iterable to be traversed.
     * @param int $size
     * @param TValue $value
     * @return array<int, TValue>
     */
    public static function pad(iterable $iterable, int $size, mixed $value): array
    {
        $array = static::from($iterable);
        $arrSize = count($array);
        $absSize = abs($size);
        if ($arrSize <= $absSize) {
            $repeated = array_fill(0, $absSize - $arrSize, $value);
            return $size > 0
                ? static::merge($array, $repeated)
                : static::merge($repeated, $array);
        }
        return $array;
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
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
     * @param array<mixed> $array
     * @param mixed ...$value
     * @return void
     */
    public static function prepend(array &$array, mixed ...$value): void
    {
        array_unshift($array, ...$value);
    }

    /**
     * Move elements that match condition to the top of the array.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param Closure(TValue, TKey): bool $condition
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function prioritize(iterable $iterable, Closure $condition, ?bool $reindex = null): array
    {
        $array = static::from($iterable);
        $reindex ??= array_is_list($array);

        $prioritized = [];
        $remains = [];
        foreach ($array as $key => $val) {
            static::verify($condition, $key, $val)
                ? $prioritized[$key] = $val
                : $remains[$key] = $val;
        }

        $result = static::merge($prioritized, $remains);

        if ($reindex) {
            static::reindex($result);
        }

        return $result;
    }

    /**
     * @template T
     * @param array<T> $array
     * @param array-key $key
     * @param bool|null $reindex
     * @return T|null
     */
    public static function pull(array &$array, int|string $key, ?bool $reindex = null): mixed
    {
        return static::pullOr($array, $key, null, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param array<TValue> $array
     * @param TKey $key
     * @param TDefault $default
     * @param bool|null $reindex
     * @return TValue|TDefault
     */
    public static function pullOr(array &$array, int|string $key, mixed $default, ?bool $reindex = null): mixed
    {
        if (!array_key_exists($key, $array)) {
            return $default;
        }

        $reindex ??= array_is_list($array);

        $value = $array[$key];
        unset($array[$key]);

        if ($reindex) {
            static::reindex($array);
        }

        return $value;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param TKey $key
     * @param bool|null $reindex
     * @return TValue
     */
    public static function pullOrFail(array &$array, int|string $key, ?bool $reindex = null): mixed
    {
        $miss = Miss::instance();
        $result = static::pullOr($array, $key, $miss, $reindex);
        if ($result instanceof Miss) {
            throw new RuntimeException("Tried to pull undefined array key \"$key\"");
        }
        return $result;
    }

    /**
     * @template TKey as array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param iterable<array-key> $keys
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function pullMany(array &$array, iterable $keys, ?bool $reindex = null): array
    {
        $reindex ??= array_is_list($array);

        $pulled = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $value = $array[$key];
                unset($array[$key]);
                $pulled[$key] = $value;
            }
        }

        if ($reindex) {
            static::reindex($array);
        }

        /** @var array<TKey, TValue> $pulled */
        return $pulled;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * @return TValue
     */
    public static function reduce(iterable $iterable, Closure $callback): mixed
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

        Assert::notNull($result);

        return $result;
    }

    /**
     * @param array<int, mixed> $array
     * @return void
     */
    public static function reindex(array &$array): void
    {
        $size = count($array);
        if ($size > 0) {
            array_splice($array, $size);
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param TValue $value
     * @param int|null $limit
     * @param bool|null $reindex
     * @return array<int, TKey>
     */
    public static function remove(array &$array, mixed $value, ?int $limit = null, ?bool $reindex = null): array
    {
        $count = 0;
        $limit ??= PHP_INT_MAX;
        $removed = [];

        // Must check before processing, since unset converts lists to assoc array.
        $reindex ??= array_is_list($array);

        foreach ($array as $key => $val) {
            if ($count < $limit && $val === $value) {
                unset($array[$key]);
                $removed[] = $key;
                ++$count;
            }
        }

        // if the list is an array, use array_splice to re-index
        if ($count > 0 && $reindex) {
            static::reindex($array);
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
        return static::pull($array, $key) !== null;
    }

    /**
     * @template T
     * @param iterable<array-key, T> $iterable Iterable to be traversed.
     * @param int $times
     * @return array<int, T>
     */
    public static function repeat(iterable $iterable, int $times): array
    {
        return iterator_to_array(Iter::repeat($iterable, $times));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function reverse(iterable $iterable, ?bool $reindex = null): array
    {
        $array = static::from($iterable);
        return array_reverse($array, !($reindex ?? array_is_list($array)));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $count
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function rotate(iterable $iterable, int $count, ?bool $reindex = null): array
    {
        $array = static::from($iterable);

        $ptr = 0;
        $result = [];
        $rotated = [];

        if ($count < 0) {
            $count = count($array) + $count;
        }

        foreach ($array as $key => $val) {
            if ($ptr < $count) {
                $rotated[$key] = $val;
            } else {
                $result[$key] = $val;
            }
            ++$ptr;
        }

        foreach ($rotated as $key => $val) {
            $result[$key] = $val;
        }

        if ($reindex ?? array_is_list($array)) {
            static::reindex($result);
        }

        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $amount
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function sampleMany(iterable $iterable, int $amount, ?bool $reindex = null): array
    {
        $array = static::from($iterable);
        /** @var array<TKey> $sampledKeys */
        $sampledKeys = (array) array_rand($array, $amount);
        return static::only($array, $sampledKeys, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public static function satisfyAll(iterable $iterable, Closure $condition): bool
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return bool
     */
    public static function satisfyAny(iterable $iterable, Closure $condition): bool
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
    public static function setIfNotExists(array &$array, int|string $key, mixed $value, ?bool &$result = null): void
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
    public static function setIfExists(array &$array, int|string $key, mixed $value, ?bool &$result = null): void
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
     * @return array<TKey, TValue>
     */
    public static function shiftMany(array &$array, int $amount): array
    {
        Assert::greaterThanEq($amount, 0);
        return array_splice($array, 0, $amount);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param bool|null $reindex
     * @return array<array-key, TValue>
     */
    public static function shuffle(iterable $iterable, ?bool $reindex = null): array
    {
        $copy = static::from($iterable);
        $size = count($copy);
        $reindex ??= array_is_list($copy);
        $array = [];
        while ($size > 0) {
            $key = array_rand($copy);
            $reindex
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $offset
     * @param int $length
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function slice(iterable $iterable, int $offset, int $length = PHP_INT_MAX, ?bool $reindex = null): array
    {
        return iterator_to_array(Iter::slice($iterable, $offset, $length, $reindex));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * @return TValue
     */
    public static function sole(iterable $iterable, ?Closure $condition = null): mixed
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $flag
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function sort(iterable $iterable, int $flag = SORT_REGULAR, ?bool $reindex = null): array
    {
        $copy = static::from($iterable);
        $reindex ??= array_is_list($copy);

        asort($copy, $flag);

        if ($reindex) {
            static::reindex($copy);
        }

        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @param int $flag
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function sortBy(iterable $iterable, Closure $callback, int $flag = SORT_REGULAR, ?bool $reindex = null): array
    {
        return static::sortByInternal($iterable, $callback, $flag, true, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @param int $flag
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function sortByDesc(iterable $iterable, Closure $callback, int $flag = SORT_REGULAR, ?bool $reindex = null): array
    {
        return static::sortByInternal($iterable, $callback, $flag, false, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @param int $flag
     * @param bool $ascending
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    protected static function sortByInternal(iterable $iterable, Closure $callback, int $flag, bool $ascending, ?bool $reindex): array
    {
        $copy = static::from($iterable);
        $reindex ??= array_is_list($copy);

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

        if ($reindex) {
            static::reindex($sorted);
        }

        return $sorted;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $flag
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function sortDesc(iterable $iterable, int $flag = SORT_REGULAR, ?bool $reindex = null): array
    {
        $copy = static::from($iterable);
        $reindex ??= array_is_list($copy);

        arsort($copy, $flag);

        if ($reindex) {
            static::reindex($copy);
        }

        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TValue): int $comparison
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function sortWith(iterable $iterable, Closure $comparison, ?bool $reindex = null): array
    {
        $copy = static::from($iterable);
        $reindex ??= array_is_list($copy);

        uasort($copy, $comparison);

        if ($reindex) {
            static::reindex($copy);
        }

        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TKey, TKey): int $comparison
     * @return array<TKey, TValue>
     */
    public static function sortWithKey(iterable $iterable, Closure $comparison): array
    {
        $copy = static::from($iterable);
        uksort($copy, $comparison);
        return $copy;
    }

    /**
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $amount
     * @return array<TKey, TValue>
     */
    public static function take(iterable $iterable, int $amount): array
    {
        return iterator_to_array(Iter::take($iterable, $amount));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return array<TKey, TValue>
     */
    public static function takeUntil(iterable $iterable, Closure $condition): array
    {
        return iterator_to_array(Iter::takeUntil($iterable, $condition));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return array<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, Closure $condition): array
    {
        return iterator_to_array(Iter::takeWhile($iterable, $condition));
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
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
     * @param iterable<TKey, TValue> $iterable1 Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function union(iterable $iterable1, iterable $iterable2, ?bool $reindex = null): array
    {
        return static::unionRecursive($iterable1, $iterable2, 1, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1 Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * @param int<1, max> $depth
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function unionRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX, ?bool $reindex = null): array
    {
        $union = static::from($iterable1);
        $reindex ??= array_is_list($union);
        foreach ($iterable2 as $key => $val) {
            if ($reindex) {
                $union[] = $val;
            } else if (!array_key_exists($key, $union)) {
                $union[$key] = $val;
            } else if ($depth > 1 && is_iterable($union[$key]) && is_iterable($val)) {
                $union[$key] = static::unionRecursive($union[$key], $val, $depth - 1);
            }
        }
        /** @var array<TKey, TValue> $union */
        return $union;
    }

    /**
     * @see uniqueBy for details
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function unique(iterable $iterable, ?bool $reindex = null): array
    {
        return static::uniqueBy($iterable, static fn($val) => $val, $reindex);
    }

    /**
     * Must do custom unique because array_unique does a string conversion before comparing.
     * For example, `[1, true, null, false]` will result in: `[0 => 1, 2 => null]` 🤦
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $callback
     * @param bool|null $reindex
     * @return array<TKey, TValue>
     */
    public static function uniqueBy(iterable $iterable, Closure $callback, ?bool $reindex = null): array
    {
        $array = static::from($iterable);
        $reindex ??= array_is_list($array);

        $refs = [];
        $preserved = [];

        foreach ($array as $key => $val) {
            $ref = static::valueToKey($callback($val, $key));
            if (!array_key_exists($ref, $refs)) {
                $refs[$ref] = null;
                $reindex
                    ? $preserved[] = $val
                    : $preserved[$key] = $val;
            }
        }
        return $preserved;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return array<int, TValue>
     */
    public static function values(iterable $iterable): array
    {
        return iterator_to_array(Iter::values($iterable));
    }

    /**
     * @template T
     * @param T|iterable<array-key, T> $value
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
     * @template TKey of array-key
     * @template TValue
     * @param Closure(TValue, TKey): bool $condition
     * @param TKey $key
     * @param TValue $val
     * @return bool
     */
    protected static function verify(Closure $condition, mixed $key, mixed $val): bool
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

    /**
     * @param mixed $val
     * @return string
     */
    protected static function valueToKey(mixed $val): string
    {
        return match(true) {
            is_null($val) => '',
            is_int($val) => "i:$val",
            is_float($val) => "f:$val",
            is_bool($val) => "b:$val",
            is_string($val) => "s:$val",
            is_array($val) => 'a:'.json_encode(array_map(static::valueToKey(...), $val), JSON_THROW_ON_ERROR),
            is_object($val) => 'o:' . spl_object_id($val),
            is_resource($val) => 'r:' . get_resource_id($val),
            default => throw new LogicException('Invalid Type: ' . Str::typeOf($val)),
        };
    }
}
