<?php

namespace Kirameki\Support;


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
        return is_array($iterable) ? $iterable : iterator_to_array($iterable);
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
     * @param $value
     * @return array
     */
    public static function wrap($value)
    {
        return is_array($value) ? $value : [$value];
    }
}