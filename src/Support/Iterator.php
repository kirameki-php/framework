<?php declare(strict_types=1);

namespace Kirameki\Support;

use Traversable;
use IteratorAggregate;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Iterator implements IteratorAggregate
{
    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(
        protected iterable $items = []
    )
    {
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
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
