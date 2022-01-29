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
class CollectionLazy implements IteratorAggregate
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
        $this->iteratorCaller = $this->arrayToIteratorCaller($source);
    }

    /**
     * @return Iterator<TKey, TValue>
     */
    public function getIterator(): Iterator
    {
        $caller = $this->iteratorCaller;
        return $caller();
    }

    /**
     * @return $this
     */
    public function dump(): static
    {
        return $this->newInstance(function () {
            foreach ($this as $key => $item) {
                dump("$key => $item");
                yield $key => $item;
            }
        });
    }

    /**
     * @param callable(TValue, TKey): void $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        return $this->newInstance(function () use ($callback) {
            foreach ($this as $key => $item) {
                $callback($item, $key);
                yield $key => $item;
            }
        });
    }

    /**
     * @param callable(TValue, TKey): bool | null $callback
     * @return $this
     */
    public function filter(?callable $callback = null): static
    {
        $callback ??= static fn ($item, $key) => !empty($item);

        return $this->newInstance(function () use ($callback) {
            foreach ($this as $key => $item) {
                if($callback($item, $key)) {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function map(callable $callback): static
    {
        return $this->newInstance(function () use ($callback) {
            foreach ($this as $key => $item) {
                yield $key => $callback($item, $key);
            }
        });
    }

    /**
     * @param Closure(): Iterator<TKey, TValue> $callback
     * @return self<TKey, TValue>
     */
    protected function newInstance(Closure $callback): self
    {
        return new self($callback);
    }

    /**
     * @return Collection<TKey, TValue>
     */
    public function collect(): Collection
    {
        return new Collection(iterator_to_array($this->getIterator()));
    }

    /**
     * @param iterable<TKey, TValue>|Closure(): Iterator<TKey, TValue> $iterable
     * @return Closure
     */
    protected function arrayToIteratorCaller(iterable|Closure $iterable): Closure
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
