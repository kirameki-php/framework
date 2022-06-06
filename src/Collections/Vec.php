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
        return $this->newInstance(Arr::keys($this));
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
     * @param int $index
     * @return bool
     */
    public function notContainsIndex(int $index): bool
    {
        return Arr::notContainsKey($this, $index);
    }
}
