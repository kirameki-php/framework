<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Iterator;
use IteratorAggregate;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class SequenceLazy implements IteratorAggregate
{
    /**
     * @var Closure(): Iterator<TKey, TValue>
     */
    public Closure $iteratorCaller;

    /**
     * @param iterable<TKey, TValue>|Closure(): Iterator<TKey, TValue> $source
     */
    public function __construct(iterable|Closure $source)
    {
        $this->iteratorCaller = $this->toIteratorCaller($source);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Iterator
    {
        return ($this->iteratorCaller)();
    }

    /**
     * @param callable(TValue, TKey): void $callback
     * @return static
     */
    public function each(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                $callback($item, $key);
                yield $key => $item;
            }
        });
    }

    /**
     * @param callable(TValue, TKey): bool $condition
     * @return static
     */
    public function filter(callable $condition): static
    {
        return new static(function () use ($condition) {
            foreach ($this as $key => $item) {
                if($condition($item, $key)) {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $callback
     * @return static<TKey, TNewValue>
     */
    public function map(callable $callback): static /** @phpstan-ignore-line */
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                yield $key => $callback($item, $key);
            }
        });
    }

    /**
     * @return Sequence<TKey, TValue>
     */
    public function toSequence(): Sequence
    {
        return new Sequence(iterator_to_array($this));
    }

    /**
     * @param iterable<TKey, TValue>|Closure(): Iterator<TKey, TValue> $iterable
     * @return Closure
     */
    protected function toIteratorCaller(iterable|Closure $iterable): Closure
    {
        if ($iterable instanceof Closure) {
            return $iterable;
        }

        return static function () use ($iterable) {
            foreach ($iterable as $key => $value) {
                yield $key => $value;
            }
        };
    }
}
