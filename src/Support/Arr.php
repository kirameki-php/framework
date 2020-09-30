<?php

namespace Kirameki\Support;

use RuntimeException;
use Traversable;

class Arr
{
    /**
     * @param array $arr
     * @return array
     */
    public static function compact(iterable $arr)
    {
        return array_filter($arr, static fn($s) => $s !== null);
    }

    /**
     * @param iterable $iterable
     * @param callable $callback
     * @return array
     */
    public static function flatMap(iterable $iterable, callable $callback)
    {
        return Arr::flatten(Arr::map(Arr::from($iterable), $callback), 1);
    }

    /**
     * @param iterable $iterable
     * @param int $depth
     * @return array
     */
    public static function flatten(iterable $iterable, int $depth = PHP_INT_MAX)
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
    public static function from(iterable $iterable)
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
     * @param iterable $arr
     * @return bool
     */
    public static function isSequential(iterable $arr): bool
    {
        foreach($arr as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable $iterable
     * @param callable $callback
     * @return array
     */
    public static function map(iterable $iterable, callable $callback)
    {
        $values = [];
        foreach ($iterable as $key => $item) {
            $values[] = $callback($item, $key);
        }
        return $values;
    }

    /**
     * @param array $arr
     * @return array
     */
    public static function shuffle(array $arr)
    {
        $copy = $arr;
        shuffle($copy);
        return $copy;
    }

    /**
     * @param array $arr
     * @param string|null $namespace
     * @return string
     */
    public static function toUrlQuery(array $arr, ?string $namespace = null)
    {
        $data = $namespace !== null ? [$namespace => $arr] : $arr;
        return http_build_query($data, '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param $value
     * @return array
     */
    public static function wrap($value)
    {
        return is_array($value) ? $value : [$value];
    }
}