<?php declare(strict_types=1);

namespace Kirameki\Support;

use ArrayAccess;
use Closure;
use Kirameki\Exception\DuplicateKeyException;
use ReflectionFunction;
use RuntimeException;
use Traversable;
use function array_column;
use function array_is_list;
use function array_key_exists;
use function array_key_last;
use function array_keys;
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
use function class_exists;
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
     * @param iterable $iterable
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
     * @param iterable $iterable
     * @return mixed
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
     * @param iterable $iterable
     * @param int $depth
     * @return array
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
            $bool = $call($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
    public static function containsKey(iterable $iterable, int|string $key): bool
    {
        $array = static::from($iterable);

        if (static::isNotDottedKey($key)) {
            return array_key_exists($key, $array);
        }

        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $ptr = static::dig($array, $segments);
        return is_array($ptr) && array_key_exists($lastSegment, $ptr);
    }

    /**
     * @param iterable $iterable
     * @return int
     */
    public static function count(iterable $iterable): int
    {
        $countable = is_countable($iterable) ? $iterable : static::from($iterable);
        return count($countable);
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
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
        return $amount >= 0
            ? array_slice(static::from($iterable), $amount)
            : array_slice(static::from($iterable), 0, -$amount);
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
     * @param int[]|string[] $keys
     * @return array
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
     * @param iterable $iterable
     * @param callable|null $condition
     * @return array
     */
    public static function filter(iterable $iterable, ?callable $condition = null): array
    {
        $condition ??= static fn($item, $key) => !empty($item);
        $values = [];
        foreach ($iterable as $key => $item) {
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
                $values[$key] = $item;
            }
        }
        return $values;
    }

    /**
     * @template T
     * @param iterable<T> $iterable
     * @param callable|null $condition
     * @return T
     */
    public static function first(iterable $iterable, ?callable $condition = null): mixed
    {
        foreach ($iterable as $key => $item) {
            if ($condition === null) {
                return $item;
            }
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
            if ($condition === null) {
                return $key;
            }
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
     * @param iterable $iterable
     * @param bool $overwrite
     * @return array
     */
    public static function flip(iterable $iterable, bool $overwrite = false): array
    {
        $flipped = [];
        foreach ($iterable as $key => $value) {
            Assert::validKey($value);
            if (!$overwrite && array_key_exists($value, $flipped)) {
                throw new DuplicateKeyException($value, $key);
            }
            $flipped[$value] = $key;
        }
        return $flipped;
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
     * @param iterable $iterable
     * @param int|string $key
     * @return mixed
     */
    public static function get(iterable $iterable, int|string $key): mixed
    {
        $keys = static::isDottedKey($key) ? explode('.', $key) : [$key];
        return static::dig(static::from($iterable), $keys);
    }

    /**
     * @param iterable $iterable
     * @param string|callable $key
     * @return array
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
     * @param array $array
     * @param int $index
     * @param mixed $value
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
     * @param iterable $iterable
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
        return array_is_list(is_array($iterable) ? $iterable : static::from($iterable));
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
     * @return array
     */
    public static function keys(iterable $iterable): array
    {
        return array_keys(static::from($iterable));
    }

    /**
     * @param iterable $iterable
     * @param string|callable $key
     * @param bool $overwrite
     * @return array
     */
    public static function keyBy(iterable $iterable, string|callable $key, bool $overwrite = false): array
    {
        return static::keyByRecursive($iterable, $key, $overwrite, 1);
    }

    /**
     * @param iterable $iterable
     * @param string|callable $key
     * @param bool $overwrite
     * @param int $depth
     * @return array
     */
    public static function keyByRecursive(iterable $iterable, string|callable $key, bool $overwrite = false, int $depth = PHP_INT_MAX): array
    {
        $callable = is_string($key) ? static::createDigger($key) : $key;

        $result = [];
        foreach ($iterable as $_key => $item) {
            $newKey = $callable($item, $_key);

            Assert::validKey($newKey);

            if (!$overwrite && array_key_exists($newKey, $result)) {
                throw new DuplicateKeyException($newKey, $item);
            }

            $result[$newKey] = ($depth > 1 && is_iterable($item))
                ? static::keyByRecursive($item, $callable, $overwrite, $depth - 1)
                : $item;
        }

        return $result;
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
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
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
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool) {
                return $key;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * @param iterable $iterable
     * @param callable $callback
     * @return array
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
     * @param iterable $iterable
     * @return mixed
     */
    public static function max(iterable $iterable): mixed
    {
        return max(static::from($iterable));
    }

    /**
     * @param iterable $iterable1
     * @param iterable $iterable2
     * @return array
     */
    public static function merge(iterable $iterable1, iterable $iterable2): array
    {
        return static::mergeRecursive($iterable1, $iterable2, 1);
    }

    /**
     * @param iterable $iterable1
     * @param iterable $iterable2
     * @param int $depth
     * @return array
     */
    public static function mergeRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $merged = static::from($iterable1);
        foreach ($iterable2 as $key => $value) {
            if (is_int($key)) {
                $merged[] = $value;
            } else if ($depth > 1 && array_key_exists($key, $merged) && is_iterable($merged[$key]) && is_iterable($value)) {
                $merged[$key] = static::mergeRecursive($merged[$key], $value, $depth - 1);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * @param iterable $iterable
     * @return mixed
     */
    public static function min(iterable $iterable): mixed
    {
        return min(static::from($iterable));
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
    public static function notContainsKey(iterable $iterable, int|string $key): bool
    {
        return !static::containsKey($iterable, $key);
    }

    /**
     * @param iterable $iterable
     * @param iterable $keys
     * @return array
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
     * @param iterable $iterable
     * @param int $size
     * @param mixed $value
     * @return array
     */
    public static function pad(iterable $iterable, int $size, mixed $value): array
    {
        return array_pad(static::from($iterable), $size, $value);
    }

    /**
     * @param iterable $iterable
     * @param int|string $key
     * @return array
     */
    public static function pluck(iterable $iterable, int|string $key): array
    {
        if (static::isNotDottedKey($key)) {
            return array_column(static::from($iterable), $key);
        }

        $plucked = [];
        $segments = explode('.', $key);
        foreach ($iterable as $values) {
            $plucked[] = static::dig($values, $segments);
        }
        return $plucked;
    }

    /**
     * @param array $array
     * @return mixed
     */
    public static function pop(array &$array): mixed
    {
        return array_pop($array);
    }

    /**
     * @param array $array
     * @param int $amount
     * @return array
     */
    public static function popMany(array &$array, int $amount): array
    {
        Assert::greaterThanOrEqualTo(0, $amount);
        return array_splice($array, -$amount);
    }

    /**
     * @param array|ArrayAccess $array
     * @param int|string $key
     * @return mixed
     */
    public static function pull(array|ArrayAccess &$array, int|string $key): mixed
    {
        if (static::isNotDottedKey($key)) {
            $value = $array[$key] ?? null;
            unset($array[$key]);
            return $value;
        }

        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $ptr = &$array;
        foreach ($segments as $segment) {
            if (!isset($segment, $ptr)) {
                return null;
            }
            $ptr = &$ptr[$segment];
        }

        if (isset($ptr[$lastSegment])) {
            $value = $ptr[$lastSegment];
            unset($ptr[$lastSegment]);
            return $value;
        }

        return null;
    }

    /**
     * @param array|ArrayAccess $array
     * @param mixed ...$value
     * @return void
     */
    public static function push(array|ArrayAccess &$array, mixed ...$value): void
    {
        foreach ($value as $v) {
            $array[] = $v;
        }
    }

    /**
     * @template T
     * @param iterable $iterable
     * @param callable $callback
     * @param T|null $initial
     * @return T
     */
    public static function reduce(iterable $iterable, callable $callback, mixed $initial = null): mixed
    {
        // Guess initial from first argument of closure if defined
        if ($initial === null) {
            $ref = new ReflectionFunction($callback);
            $refType = $ref->getParameters()[0]->getType();
            $name = $refType?->getName();
            if ($name === 'int') {
                $initial = 0;
            } else if ($name === 'float') {
                $initial = 0.0;
            } else if ($name === 'string') {
                $initial = '';
            } else if ($name === 'array') {
                $initial = [];
            } else if ($name !== null && class_exists($name)) {
                $initial = new $name;
            } else {
                throw new RuntimeException('Initial value not set and not guessable.');
            }
        }

        $result = $initial ?? [];
        foreach ($iterable as $key => $item) {
            $result = $callback($result, $item, $key);
        }
        return $result;
    }

    /**
     * @param iterable $iterable
     * @param mixed $value
     * @param int|null $limit
     * @return int
     */
    public static function remove(iterable &$iterable, mixed $value, ?int $limit = null): int
    {
        $counter = 0;
        $limit ??= PHP_INT_MAX;
        foreach ($iterable as $key => $item) {
            if ($counter < $limit && $item === $value) {
                unset($iterable[$key]);
                $counter++;
            }
        }
        return $counter;
    }

    /**
     * @param array|ArrayAccess $array
     * @param int|string $key
     * @return bool
     */
    public static function removeKey(array|ArrayAccess &$array, int|string $key): bool
    {
        if (static::isNotDottedKey($key)) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                return true;
            }
            return false;
        }

        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        $ptr = &$array;
        foreach ($segments as $segment) {
            if (is_array($ptr) && array_key_exists($segment, $ptr)) {
                $ptr = &$ptr[$segment];
            } else {
                return false;
            }
        }

        if (array_key_exists($lastSegment, $ptr)) {
            unset($ptr[$lastSegment]);
            return true;
        }

        return false;
    }

    /**
     * @param iterable $iterable
     * @param int $times
     * @return array
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
     * @param iterable $iterable
     * @return array
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
     * @return T[]
     */
    public static function sampleMany(iterable $iterable, int $amount): array
    {
        $array = static::from($iterable);
        $sampledKeys = array_rand($array, $amount);
        return static::only($array, $sampledKeys);
    }

    /**
     * @param iterable $iterable
     * @param callable $condition
     * @return bool
     */
    public static function satisfyAll(iterable $iterable, callable $condition): bool
    {
        foreach ($iterable as $key => $item) {
            $bool = $condition($item, $key);
            Assert::bool($bool);
            if ($bool === false) {
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
     * @param array|ArrayAccess $array
     * @param int|string $key
     * @param mixed $value
     */
    public static function set(array|ArrayAccess &$array, int|string $key, mixed $value): void
    {
        if (static::isNotDottedKey($key)) {
            $array[$key] = $value;
            return;
        }

        $ptr = &$array;
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);
        foreach ($segments as $segment) {
            $ptr[$segment] ??= [];
            $ptr = &$ptr[$segment];
        }
        $ptr[$lastSegment] = $value;
    }

    /**
     * @param array $array
     * @return mixed
     */
    public static function shift(array &$array): mixed
    {
        return array_shift($array);
    }

    /**
     * @param array $array
     * @param int $amount
     * @return array
     */
    public static function shiftMany(array &$array, int $amount): array
    {
        Assert::greaterThanOrEqualTo(0, $amount);
        return array_splice($array, $amount);
    }

    /**
     * @param iterable $iterable
     * @return array
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
     * @param iterable $iterable
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
     * @param iterable $iterable
     * @param int $amount
     * @return array
     */
    public static function take(iterable $iterable, int $amount): array
    {
        return $amount > 0
            ? array_slice(static::from($iterable), 0, $amount)
            : array_slice(static::from($iterable), $amount, -$amount);
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
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param iterable $iterable1
     * @param iterable $iterable2
     * @return array
     */
    public static function union(iterable $iterable1, iterable $iterable2): array
    {
        return static::unionRecursive($iterable1, $iterable2, 1);
    }

    /**
     * @param iterable $iterable1
     * @param iterable $iterable2
     * @param int $depth
     * @return array
     */
    public static function unionRecursive(iterable $iterable1, iterable $iterable2, int $depth = PHP_INT_MAX): array
    {
        $union = static::from($iterable1);
        foreach ($iterable2 as $key => $value) {
            if (is_int($key)) {
                $union[] = $value;
            } else if (!array_key_exists($key, $union)) {
                $union[$key] = $value;
            } else if ($depth > 1 && is_iterable($union[$key]) && is_iterable($value)) {
                $union[$key] = static::unionRecursive($union[$key], $value, $depth - 1);
            }
        }
        return $union;
    }

    /**
     * @see uniqueBy for details
     *
     * @param iterable $iterable
     * @return array
     */
    public static function unique(iterable $iterable): array
    {
        return static::uniqueBy($iterable, static fn($value) => $value);
    }

    /**
     * Must do custom unique because array_unique does a string convertion before comparing.
     * For example, `[1, true, null, false]` will result in: `[0 => 1, 2 => null]` 🤦
     *
     * @param iterable $iterable
     * @param callable $callback
     * @return array
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
     * @param array $array
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
     * @param iterable $iterable
     * @return array
     */
    public static function values(iterable $iterable): array
    {
        return array_values(static::from($iterable));
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
}
