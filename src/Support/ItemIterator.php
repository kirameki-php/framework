<?php declare(strict_types=1);

namespace Kirameki\Support;

use Generator;
use IteratorAggregate;
use function is_array;
use function iterator_to_array;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class ItemIterator implements IteratorAggregate
{
    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(
        protected iterable $items
    ) {
    }

    /**
     * @return Generator<TKey, TValue>
     */
    public function getIterator(): Generator
    {
        yield from $this->items;
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return Arr::from($this);
    }

    /**
     * @return Sequence<TKey, TValue>
     */
    public function toSequence(): Sequence
    {
        return new Sequence($this);
    }
}
