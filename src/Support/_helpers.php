<?php declare(strict_types=1);

use Kirameki\Support\Collection;
use Kirameki\Support\Str;

function blank(?string $string): bool
{
    return Str::isBlank($string);
}

/**
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue>|null $items
 * @return Collection<TKey, TValue>
 */
function collect(?iterable $items = null): Collection
{
    return new Collection($items);
}

/**
 * @param string|object $class
 * @return string
 */
function class_basename(string|object $class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}

/**
 * @template T
 * @param T $value
 * @param callable $callable
 * @return T
 */
function tap(mixed $value, callable $callable)
{
    $callable($value);
    return $value;
}
