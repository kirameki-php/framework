<?php declare(strict_types=1);

namespace Kirameki\Collections;

/**
 * @template TValue
 * @extends Enumerable<int, TValue>
 */
class Vec extends Enumerable
{
    protected bool $isList = true;

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
        return $this->newInstance(Iter::keys($this));
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
     * @param int $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return $this->newInstance(Iter::repeat($this, $times));
    }

    /**
     * @param int $index
     * @return bool
     */
    public function notContainsIndex(int $index): bool
    {
        return Arr::notContainsKey($this, $index);
    }
}
