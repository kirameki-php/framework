<?php

namespace Kirameki\Support;

use RuntimeException;
use Traversable;

class Arr
{
    /**
     * @param array $iterable
     * @return array
     */
    public static function compact(iterable $iterable): array
    {
        return array_filter(static::from($iterable), static fn($s) => $s !== null);
    }

    /**
     * @param iterable $iterable
     * @param mixed|callable $value
     * @return bool
     */
    public static function contains(iterable $iterable, $value): bool
    {
        $call = is_callable($value) ? $value : static fn($item) => $item === $value;
        foreach ($iterable as $key => $item) {
            if (Assert::isTrue($call($item, $key))) {
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
            if (Assert::isTrue($condition($item, $key))) {
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
            if (Assert::isTrue($result)) {
                $values[$key] = $item;
            }
        }
        return $values;
    }

    /**
     * @param iterable $iterable
     * @param callable|null $condition
     * @return mixed|null
     */
    public static function first(iterable $iterable, ?callable $condition = null)
    {
        foreach ($iterable as $key => $item) {
            if ($condition === null || Assert::isTrue($condition($item, $key))) {
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
            if (Assert::isTrue($condition($item, $key))) {
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
    public static function firstKey(iterable $iterable, ?callable $condition = null)
    {
        foreach ($iterable as $key => $item) {
            if ($condition === null || Assert::isTrue($condition($item, $key))) {
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
        return static::flatten(static::map(static::from($iterable), $callback), 1);
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
                if (!is_iterable($value) || $depth === 0) {
                    $results[] = $value;
                } else {
                    $func($value, $depth - 1);
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
    public static function isSequential(iterable $iterable): bool
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
     * @param callable|null $condition
     * @return mixed|null
     */
    public static function last(iterable $iterable, ?callable $condition = null)
    {
        $copy = static::from($iterable);
        if ($condition === null) {
            return end($copy);
        }
        foreach (array_reverse($copy, true) as $key => $item) {
            if (Assert::isTrue($condition($item, $key))) {
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
    public static function lastIndex(iterable $iterable, callable $condition): ?int
    {
        $copy = static::from($iterable);
        $count = count($copy) - 1;
        foreach (array_reverse($copy) as $key => $item) {
            $count--;
            if (Assert::isTrue($condition($item, $key))) {
                return $count;
            }
        }
        return null;
    }

    /**
     * @param iterable $iterable
     * @param callable|null $condition
     * @return int|string|null
     */
    public static function lastKey(iterable $iterable, ?callable $condition = null)
    {
        $copy = static::from($iterable);

        if ($condition === null) {
            return array_key_last($copy);
        }

        foreach(array_reverse($copy, true) as $key => $item) {
            if (Assert::isTrue($condition($item, $key))) {
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
    public static function map(iterable $iterable, callable $callback): array
    {
        $values = [];
        foreach ($iterable as $key => $item) {
            $values[] = $callback($item, $key);
        }
        return $values;
    }

    /**
     * @param iterable $iterable
     * @param mixed|callable $value
     * @return bool
     */
    public static function notContains(iterable $iterable, $value): bool
    {
        return !static::contains($iterable, $value);
    }

    /**
     * @param iterable $iterable
     * @return mixed
     */
    public static function sample(iterable $iterable)
    {
        $arr = static::from($iterable);
        return $arr[array_rand($arr)];
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
     * @param $value
     * @return array
     */
    public static function wrap($value): array
    {
        return is_array($value) ? $value : [$value];
    }
}