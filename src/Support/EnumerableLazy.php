<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Iterator;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Enumerable<TKey, TValue>
 */
class EnumerableLazy extends Enumerable
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
        return $this->newInstance(function () use ($callback) {
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
        return $this->newInstance(function () use ($condition) {
            foreach ($this as $key => $item) {
                if($condition($item, $key)) {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * @template TNew
     * @param callable(TValue, TKey): TNew $callback
     * @return static<TKey, TNew>
     */
    public function map(callable $callback): static /** @phpstan-ignore-line */
    {
        return $this->newInstance(function () use ($callback) {
            foreach ($this as $key => $item) {
                yield $key => $callback($item, $key);
            }
        });
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
