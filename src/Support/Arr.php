<?php

namespace Kirameki\Support;

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
     * @param callable $callback
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
     */
    public static function eachChunk(iterable $iterable, int $size, callable $callback)
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
     * @param mixed|callable $value
     * @return bool
     */
    public static function notContains(iterable $iterable, mixed $value): bool
    {
        return !static::contains($iterable, $value);
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
}
