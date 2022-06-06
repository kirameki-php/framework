<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Webmozart\Assert\Assert;

/**
 * @template TValue
 * @extends MutableCollection<int, TValue>
 */
class Vec extends MutableCollection
{
    /**
     * @param iterable<int, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        parent::__construct($items, true);
    }

    /**
     * @param TValue ...$value
     * @return $this
     */
    public function append(mixed ...$value): static
    {
        Arr::append($this->items, ...$value);
        return $this;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function containsIndex(int $index): bool
    {
        return Arr::containsKey($this, $index);
    }

    /**
     * @return static<int>
     */
    public function indices(): static
    {
        return $this->newInstance(Arr::keys($this));
    }

    /**
     * @param int $index
     * @return bool
     */
    public function notContainsIndex(int $index): bool
    {
        return Arr::notContainsKey($this, $index);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param int|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            Assert::integer($offset);
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param int $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        Arr::pull($this->items, $offset);
    }

    /**
     * @param int $size
     * @param TValue $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->newInstance(Arr::pad($this, $size, $value));
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->newInstance(Arr::repeat($this, $times));
    }

    /**
     * @param mixed ...$value
     * @return $this
     */
    public function prepend(mixed ...$value): static
    {
        Arr::prepend($this->items, ...$value);
        return $this;
    }
}
