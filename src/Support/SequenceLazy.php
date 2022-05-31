<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Kirameki\Collections\Arr;
use Kirameki\Collections\Iter;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Sequence<TKey, TValue>
 */
class SequenceLazy extends Sequence
{
    /**
     * @inheritDoc
     */
    public function chunk(int $size): static
    {
        return new static(Iter::chunk($this, $size));
    }

    /**
     * @inheritDoc
     */
    public function compact(int $depth = 1): static
    {
        return new static(Arr::compact($this, $depth));
    }

    /**
     * @inheritDoc
     */
    public function drop(int $amount): static
    {
        return new static(Iter::drop($this, $amount));
    }

    /**
     * @inheritDoc
     */
    public function dropUntil(Closure $condition): static
    {
        return new static(Iter::dropUntil($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function dropWhile(Closure $condition): static
    {
        return new static(Iter::dropWhile($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function each(Closure $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                $callback($item, $key);
                yield $key => $item;
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function filter(Closure $condition): static
    {
        return new static(Iter::filter($this, $condition));
    }

    /**
     * @return static
     */
    public function keys(): static
    {
        return new static(Iter::keys($this));
    }

    /**
     * @inheritDoc
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * @return static<TKey, TMapValue>
     */
    public function map(Closure $callback): static
    {
        return new static(Iter::map($this, $callback));
    }

    /**
     * @inheritDoc
     */
    public function take(int $amount): static
    {
        return new static(Iter::take($this, $amount));
    }

    /**
     * @inheritDoc
     */
    public function takeUntil(Closure $condition): static
    {
        return new static(Iter::takeUntil($this, $condition));
    }

    /**
     * @inheritDoc
     */
    public function takeWhile(Closure $condition): static
    {
        return new static(Iter::takeWhile($this, $condition));
    }

    /**
     * @return static
     */
    public function values(): static
    {
        return new static(Iter::values($this));
    }

    /**
     * @return Sequence<TKey, TValue>
     */
    public function eager(): Sequence
    {
        return new Sequence($this->toArray());
    }
}
