<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Iterator;
use Kirameki\Exception\InvalidKeyException;
use Webmozart\Assert\Assert;
use function count;
use function is_int;
use function is_iterable;
use function is_string;

class Iter
{
    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $size Size of each chunk. Must be >= 1.
     * @param bool $reindex
     * @return Iterator<int, array<TKey, TValue>>
     */
    public static function chunk(iterable $iterable, int $size, bool $reindex = false): Iterator
    {
        Assert::positiveInteger($size);

        $remaining = $size;
        $chunk = [];
        foreach ($iterable as $key => $val) {
            $reindex
                ? $chunk[] = $val
                : $chunk[$key] = $val;

            if (--$remaining === 0) {
                yield $chunk;
                $remaining = $size;
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            yield $chunk;
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param bool $reindex
     * @return Iterator<TKey, TValue>
     */
    public static function compact(iterable $iterable, bool $reindex = false): Iterator
    {
        foreach ($iterable as $key => $val) {
            if ($val !== null) {
                if ($reindex) {
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $amount Amount of elements to drop. Must be >= 0.
     * @param bool $reindex
     * @return Iterator<TKey, TValue>
     */
    public static function drop(iterable $iterable, int $amount, bool $reindex = false): Iterator
    {
        Assert::greaterThanEq($amount, 0);
        return static::slice($iterable, $amount, PHP_INT_MAX, $reindex);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @param bool $reindex
     * @return Iterator<TKey, TValue>
     */
    public static function dropUntil(iterable $iterable, Closure $condition, bool $reindex = false): Iterator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && static::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @param bool $reindex
     * @return Iterator<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, Closure $condition, bool $reindex = false): Iterator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && !static::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @param bool $reindex
     * @return Iterator<TKey, TValue>
     */
    public static function filter(iterable $iterable, Closure $condition, bool $reindex = false): Iterator
    {
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                if ($reindex) {
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * @return Iterator<int, mixed>
     */
    public static function flatMap(iterable $iterable, Closure $callback): Iterator
    {
        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);
            if (is_iterable($result)) {
                foreach ($result as $each) {
                    yield $each;
                }
            } else {
                yield $result;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @param int $depth Depth must be >= 1. Default: 1.
     * @return Iterator<mixed, mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): Iterator
    {
        Assert::positiveInteger($depth);
        return static::flattenImpl($iterable, $depth);
    }

    /**
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @param int $depth Depth must be >= 1. Default: 1.
     * @return Iterator<mixed, mixed>
     */
    protected static function flattenImpl(iterable $iterable, int $depth = 1): Iterator
    {
        foreach ($iterable as $key => $val) {
            if (is_iterable($val) && $depth > 0) {
                foreach (static::flattenImpl($val, $depth - 1) as $_key => $_val) {
                    yield $_key => $_val;
                }
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return Iterator<TValue, TKey>
     */
    public static function flip(iterable $iterable): Iterator
    {
        foreach ($iterable as $key => $val) {
            if (is_int($val) || is_string($val)) {
                yield $val => $key;
            } else {
                throw new InvalidKeyException($val);
            }
        }
    }

    /**
     * @template TKey
     * @param iterable<TKey, mixed> $iterable Iterable to be traversed.
     * @return Iterator<int, TKey>
     */
    public static function keys(iterable $iterable): Iterator
    {
        foreach ($iterable as $key => $item) {
            yield $key;
        }
    }

    /**
     * @template TKey
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return Iterator<TKey, TMapValue>
     */
    public static function map(iterable $iterable, Closure $callback): Iterator
    {
        foreach ($iterable as $key => $val) {
            yield $key => $callback($val, $key);
        }
    }

    /**
     * @template T
     * @param iterable<array-key, T> $iterable Iterable to be traversed.
     * @param int<0, max> $times
     * @return Iterator<int, T>
     */
    public static function repeat(iterable $iterable, int $times): Iterator
    {
        Assert::greaterThanEq($times, 0);

        for ($i = 0; $i < $times; $i++) {
            foreach ($iterable as $val) {
                yield $val;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $offset
     * @param int $length
     * @param bool $reindex
     * @return Iterator<TKey, TValue>
     */
    public static function slice(iterable $iterable, int $offset, int $length = PHP_INT_MAX, bool $reindex = false): Iterator
    {
        $isNegativeOffset = $offset < 0;
        $isNegativeLength = $length < 0;

        if ($isNegativeOffset || $isNegativeLength) {
            $count = 0;
            foreach ($iterable as $_) {
                ++$count;
            }
            if ($isNegativeOffset) {
                $offset = $count + $offset;
            }
            if ($isNegativeLength) {
                $length = $count + $length;
            }
        }

        $i = 0;
        foreach ($iterable as $key => $val) {
            if ($i++ < $offset) {
                continue;
            }

            if ($i > $offset + $length) {
                break;
            }

            if ($reindex) {
                yield $val;
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $amount
     * @return Iterator<TKey, TValue>
     */
    public static function take(iterable $iterable, int $amount): Iterator
    {
        Assert::greaterThanEq($amount, 0);
        return static::slice($iterable, 0, $amount);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function takeUntil(iterable $iterable, Closure $condition): Iterator
    {
        foreach ($iterable as $key => $item) {
            if (!$condition($item, $key)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, Closure $condition): Iterator
    {
        foreach ($iterable as $key => $item) {
            if ($condition($item, $key)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return Iterator<int, TValue>
     */
    public static function values(iterable $iterable): Iterator
    {
        foreach ($iterable as $val) {
            yield $val;
        }
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
}
