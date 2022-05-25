<?php declare(strict_types=1);

namespace Kirameki\Support;

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
     * @return Iterator<int, array<TKey, TValue>>
     */
    public static function chunk(iterable $iterable, int $size): Iterator
    {
        Assert::positiveInteger($size);

        $remaining = $size;
        $chunk = [];
        foreach ($iterable as $key => $val) {
            $isList ??= $key === 0;

            $isList
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
     * @param int $depth Optional. Must be >= 1. Default is 1.
     * @return Iterator<TKey, TValue>
     */
    public static function compact(iterable $iterable, int $depth = 1): Iterator
    {
        foreach ($iterable as $key => $val) {
            $isList ??= $key === 0;
            if (is_iterable($val) && $depth > 1) {
                $val = Arr::compact($val, $depth - 1); /** @phpstan-ignore-line */
            }
            if ($val !== null) {
                if ($isList) {
                    yield $val; /** @phpstan-ignore-line */
                } else {
                    yield $key => $val; /** @phpstan-ignore-line */
                }
            }
        }
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param int $amount Amount of elements to drop. Must be >= 0.
     * @return Iterator<TKey, TValue>
     */
    public static function drop(iterable $iterable, int $amount): Iterator
    {
        Assert::greaterThanEq($amount, 0);
        return static::slice($iterable, $amount);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param callable(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function dropUntil(iterable $iterable, callable $condition): Iterator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            $isList ??= $key === 0;

            if ($drop && static::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($isList) {
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
     * @param callable(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, callable $condition): Iterator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            $isList ??= $key === 0;

            if ($drop && !static::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($isList) {
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
     * @param callable(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function filter(iterable $iterable, callable $condition): Iterator
    {
        foreach ($iterable as $key => $val) {
            $isList ??= $key === 0;
            if (static::verify($condition, $key, $val)) {
                if ($isList) {
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
     * @param callable(TValue, TKey): mixed $callback
     * @return Iterator<int, mixed>
     */
    public static function flatMap(iterable $iterable, callable $callback): Iterator
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
     * @param callable(TValue, TKey): TMapValue $callback
     * @return Iterator<TKey, TMapValue>
     */
    public static function map(iterable $iterable, callable $callback): Iterator
    {
        foreach ($iterable as $key => $val) {
            yield $key => $callback($val, $key);
        }
    }

    /**
     * @template T
     * @param iterable<array-key, T> $iterable Iterable to be traversed.
     * @param int $times
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
     * @return Iterator<TKey, TValue>
     */
    public static function slice(iterable $iterable, int $offset, int $length = PHP_INT_MAX): Iterator
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
        foreach ($iterable as $key => $value) {
            if ($i++ < $offset) {
                continue;
            }

            if ($i > $offset + $length) {
                break;
            }

            if (is_int($key)) {
                yield $value;
            } else {
                yield $key => $value;
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
     * @param callable(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function takeUntil(iterable $iterable, callable $condition): Iterator
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
     * @param callable(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, callable $condition): Iterator
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
     * @param callable(TValue, TKey): bool $condition
     * @param TKey $key
     * @param TValue $val
     * @return bool
     */
    protected static function verify(callable $condition, mixed $key, mixed $val): bool
    {
        return $condition($val, $key);
    }
}
