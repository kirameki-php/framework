<?php

namespace Kirameki\Support;

use Closure;
use Kirameki\Exception\DuplicateKeyException;
use Kirameki\Exception\InvalidValueException;
use RuntimeException;
use Traversable;

class Arr
{
    /**
     * @param iterable $iterable
     * @param int $depth
     * @return array
     */
    public static function compact(iterable $iterable, int $depth = PHP_INT_MAX): array
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
        return $result;
    }

    /**
     * @param iterable $iterable
     * @param mixed|callable $value
     * @return bool
     */
    public static function contains(iterable $iterable, mixed $value): bool
    {
        $call = is_callable($value) ? $value : static fn($item) => $item === $value;
        foreach ($iterable as $key => $item) {
            if (Check::isTrue($call($item, $key))) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param iterable $iterable
     * @param int|string $key
     * @return bool
     */
    public static function containsKey(iterable $iterable, mixed $key): bool
    {
        Assert::validKey($key);

        $array = static::from($iterable);

        if (static::isNotDottedKey($key)) {
            return array_key_exists($key, $array);
        }

        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $ptr = static::digTo($array, $segments);
        return is_array($ptr) && array_key_exists($lastSegment, $ptr);
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return int
     */
    public static function countBy(iterable $iterable, callable $condition): int
    {
        $counter = 0;
        foreach ($iterable as $key => $item) {
            if (Check::isTrue($condition($item, $key))) {
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * @param iterable $iterable
     * @param int $amount
     * @return array
     */
    public static function drop(iterable $iterable, int $amount): array
    {
        if ($amount < 0) {
            throw new InvalidValueException('positive value', $amount);
        }
        return array_slice(static::from($iterable), $amount);
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return array
     */
    public static function dropUntil(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, $condition) ?? PHP_INT_MAX;
        return static::drop($iterable, $index);
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return array
     */
    public static function dropWhile(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, static function ($item, $key) use ($condition) {
                $result = $condition($item, $key);
                Assert::bool($result);
                return !$result;
            }) ?? PHP_INT_MAX;
        return static::drop($iterable, $index);
    }

    /**
     * @param iterable $iterable
     * @param int|string $key
     * @return mixed
     */
    public static function get(iterable $iterable, mixed $key): mixed
    {
        Assert::validKey($key);

        $keys = static::isDottedKey($key) ? explode('.', $key) : [$key];
        return static::digTo(static::from($iterable), $keys);
    }

    /**
     * @param iterable $iterable
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
     * @param iterable $iterable
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
     * @param iterable $iterable
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
     * @param iterable $iterable
     * @param int|string ...$key
     * @return array
     */
    public static function except(iterable $iterable, ...$key): array
    {
        $copy = static::from($iterable);
        foreach ($key as $k) {
            unset($copy[$k]);
        }
        return $copy;
    }

    /**
     * @param iterable $iterable
     * @param callable|null $condition
     * @return array
     */
    public static function filter(iterable $iterable, ?callable $condition = null): array
    {
        $condition ??= static fn($item, $key) => !empty($item);
        $values = [];
        foreach ($iterable as $key => $item) {
            $result = $condition($item, $key);
            if (Check::isTrue($result)) {
                $values[$key] = $item;
            }
        }
        return $values;
    }

    /**
     * @param iterable $iterable
     * @param callable|null $condition
     * @return mixed
     */
    public static function first(iterable $iterable, ?callable $condition = null): mixed
    {
        foreach ($iterable as $key => $item) {
            if ($condition === null || Check::isTrue($condition($item, $key))) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return int|null
     */
    public static function firstIndex(iterable $iterable, callable $condition): ?int
    {
        $count = 0;
        foreach ($iterable as $key => $item) {
            if (Check::isTrue($condition($item, $key))) {
                return $count;
            }
            $count++;
        }
        return null;
    }

    /**
     * @param iterable $iterable
     * @param callable|null $condition
     * @return int|string|null
     */
    public static function firstKey(iterable $iterable, ?callable $condition = null): int|string|null
    {
        foreach ($iterable as $key => $item) {
            if ($condition === null || Check::isTrue($condition($item, $key))) {
                return $key;
            }
        }
        return null;
    }

    /**
     * @param iterable $iterable
     * @param callable $callback
     * @return array
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
     * @param iterable $iterable
     * @param int $depth
     * @return array
     */
    public static function flatten(iterable $iterable, int $depth = PHP_INT_MAX): array
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
     * @param iterable $iterable
     * @return array
     */
    public static function from(iterable $iterable): array
    {
        if (is_array($iterable)) {
            return $iterable;
        }
        if ($iterable instanceof Traversable) {
            return iterator_to_array($iterable);
        }
        throw new RuntimeException('Unknown type:'.get_class($iterable));
    }

    /**
     * @param string|callable $key
     * @return array
     */
    public static function groupBy(iterable $iterable, string|callable $key): array
    {
        $call = is_string($key) ? static::createDigCallback($key) : $key;
        $map = [];
        foreach ($iterable as $k => $item) {
            $groupKey = $call($item, $k);
            if (is_string($groupKey) || is_int($groupKey)) {
                $map[$groupKey] ??= [];
                $map[$groupKey][] = $item;
            }
        }
        return $map;
    }

    /**
     * @param iterable $iterable
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
     * @param iterable $iterable
     * @return bool
     */
    public static function isAssoc(iterable $iterable): bool
    {
        foreach($iterable as $key => $value) {
            if (!is_string($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable $iterable
     * @return bool
     */
    public static function isEmpty(iterable $iterable): bool
    {
        if (is_array($iterable)) {
            return empty($iterable);
        }

        foreach ($iterable as $_) {
            return false;
        }

        return true;
    }

    /**
     * @param iterable $iterable
     * @return bool
     */
    public static function isList(iterable $iterable): bool
    {
        foreach($iterable as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable $iterable
     * @return bool
     */
    public static function isNotEmpty(iterable $iterable): bool
    {
        return !static::isEmpty($iterable);
    }

    /**
     * @param iterable $iterable
     * @param string|callable $key
     * @param bool $overwrite
     * @return array
     */
    public static function keyBy(iterable $iterable, string|callable $key, bool $overwrite = false): array
    {
        $call = is_string($key) ? static::createDigCallback($key) : $key;
        $map = [];
        foreach ($iterable as $k => $item) {
            $newKey = $call($item, $k);
            if (!$overwrite && array_key_exists($newKey, $map)) {
                throw new DuplicateKeyException($newKey, $item);
            }
            $map[$newKey] = $item;
        }
        return $map;
    }

    /**
     * @param iterable $iterable
     * @param callable|null $condition
     * @return mixed
     */
    public static function last(iterable $iterable, ?callable $condition = null): mixed
    {
        $copy = static::from($iterable);
        end($copy);

        if ($condition === null) {
            return current($copy);
        }

        while(($key = key($copy)) !== null) {
            $item = current($copy);
            if (Check::isTrue($condition($item, $key))) {
                return $item;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @param iterable $iterable
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
            if (Check::isTrue($condition($item, $key))) {
                return $count;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @param iterable $iterable
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
            if (Check::isTrue($condition($item, $key))) {
                return $key;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @param iterable $iterable
     * @param callable|string $callback
     * @return array
     */
    public static function map(iterable $iterable, callable|string $callback): array
    {
        if (is_string($callback)) {
            $callback = static::createDigCallback($callback);
        }

        $values = [];
        foreach ($iterable as $key => $item) {
            $values[$key] = $callback($item, $key);
        }
        return $values;
    }

    /**
     * @param iterable $iterable
     * @return array
     */
    public static function minMax(iterable $iterable): array
    {
        $min = null;
        $max = null;
        foreach ($iterable as $value) {
            if ($min === null || $min > $value) {
                $min = $value;
            }
            if ($max === null || $max < $value) {
                $max = $value;
            }
        }
        return [$min, $max];
    }

    /**
     * @param iterable $iterable
     * @param mixed|callable $value
     * @return bool
     */
    public static function notContains(iterable $iterable, mixed $value): bool
    {
        return !static::contains($iterable, $value);
    }

    /**
     * @param iterable $iterable
     * @param mixed|callable $key
     * @return bool
     */
    public static function notContainsKey(iterable $iterable, mixed $key): bool
    {
        return !static::containsKey($iterable, $key);
    }

    /**
     * @param iterable $iterable
     * @param int|string ...$key
     * @return array
     */
    public static function only(iterable $iterable, ...$key): array
    {
        $copy = static::from($iterable);
        $array = [];
        foreach ($key as $k) {
            $array[$k] = $copy[$k];
        }
        return $array;
    }

    /**
     * @param iterable $iterable
     * @param int|string $key
     * @return array
     */
    public static function pluck(iterable $iterable, mixed $key): array
    {
        Assert::validKey($key);

        if (static::isNotDottedKey($key)) {
            return array_column(static::from($iterable), $key);
        }

        $plucked = [];
        $segments = explode('.', $key);
        foreach ($iterable as $values) {
            $plucked[] = static::digTo($values, $segments);
        }
        return $plucked;
    }

    /**
     * @param iterable $iterable
     * @param mixed $value
     * @param int|null $limit
     * @return void
     */
    public static function remove(iterable &$iterable, mixed $value, ?int $limit = null): void
    {
        $counter = 0;
        foreach ($iterable as $key => $item) {
            if ($counter < $limit && $item === $value) {
                unset($iterable[$key]);
                $counter++;
            }
        }
    }

    /**
     * @param iterable $iterable
     * @return mixed
     */
    public static function sample(iterable $iterable): mixed
    {
        $arr = static::from($iterable);
        return $arr[array_rand($arr)];
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return bool
     */
    public static function satisfyAll(iterable $iterable, callable $condition): bool
    {
        foreach ($iterable as $item) {
            if (Check::isFalse($condition($item))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return bool
     */
    public static function satisfyAny(iterable $iterable, callable $condition): bool
    {
        return static::contains($iterable, $condition);
    }

    /**
     * @param iterable $iterable
     * @return array
     */
    public static function shuffle(iterable $iterable): array
    {
        $copy = static::from($iterable);
        shuffle($copy);
        return $copy;
    }

    /**
     * @param iterable $iterable
     * @param int $amount
     * @return array
     */
    public static function take(iterable $iterable, int $amount): array
    {
        $array = static::from($iterable);
        if ($amount < 0) {
            throw new InvalidValueException('positive value', $amount);
        }
        return array_slice($array, 0, $amount);
    }

    /**
     * @param iterable $iterable
     * @param callable $callback
     * @return array
     */
    public static function takeUntil(iterable $iterable, callable $callback): array
    {
        $index = static::firstIndex($iterable, $callback) ?? PHP_INT_MAX;
        return static::take($iterable, $index);
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return array
     */
    public static function takeWhile(iterable $iterable, callable $condition): array
    {
        $index = static::firstIndex($iterable, static fn($item, $key) => !$condition($item, $key)) ?? PHP_INT_MAX;
        return static::take($iterable, $index);
    }

    /**
     * @param iterable $iterable
     * @return array
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
     * @param iterable $iterable
     * @param string|null $namespace
     * @return string
     */
    public static function toUrlQuery(iterable $iterable, ?string $namespace = null): string
    {
        $arr = static::from($iterable);
        $data = $namespace !== null ? [$namespace => $arr] : $arr;
        return http_build_query($data, '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function transformKeys(array $array, callable $callback): array
    {
        return static::transformKeysRecursive($array, $callback, 1);
    }

    /**
     * @param iterable $iterable
     * @param callable $callback
     * @param int $depth
     * @return array
     */
    public static function transformKeysRecursive(iterable $iterable, callable $callback, int $depth = PHP_INT_MAX): array
    {
        $result = [];

        foreach ($iterable as $key => $item) {
            $newKey = $callback($key, $item);

            Assert::validKey($newKey);

            $result[$newKey] = ($depth > 1 && is_iterable($item))
                ? static::transformKeysRecursive($item, $callback, $depth - 1)
                : $item;
        }

        return $result;
    }

    /**
     * @param iterable $iterable1
     * @param iterable $iterable2
     * @return array
     */
    public static function unionKeys(iterable $iterable1, iterable $iterable2): array
    {
        return static::unionKeysRecursive($iterable1, $iterable2);
    }

    /**
     * @param iterable $iterable1
     * @param iterable $iterable2
     * @param int $depth
     * @return array
     */
    public static function unionKeysRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $array1 = static::from($iterable1);
        foreach ($iterable2 as $key => $value) {
            if ($depth >= 1 && is_iterable($array1[$key]) && is_iterable($value)) {
                $value = static::unionKeysRecursive($array1[$key], $value, $depth - 1);
            }
            if (!array_key_exists($key, $array1)) {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    /**
     * @param mixed $value
     * @return array
     */
    public static function wrap(mixed $value): array
    {
        return is_array($value) ? $value : [$value];
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

    /**
     * @private
     * @param string $key
     * @return Closure
     */
    protected static function createDigCallback(string $key): Closure
    {
        $segments = explode('.', $key);
        return static fn($v, $k) => static::digTo($v, $segments);
    }
}
