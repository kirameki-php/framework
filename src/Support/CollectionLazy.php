<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Iterator;
use IteratorAggregate;

/**
 * @template T
 */
class CollectionLazy implements IteratorAggregate
{
    /**
     * @var Closure
     */
    public Closure $iteratorCaller;

    /**
     * @param iterable|Closure $source
     */
    public function __construct(iterable|Closure $source)
    {
        $this->iteratorCaller = $this->arrayToIteratorCaller($source);
    }

    /**
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        return call_user_func($this->iteratorCaller);
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
     * @param callable $callback
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
     * @param callable|null $callback
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
     * @param Closure $callback
     * @return $this
     */
    protected function newInstance(Closure $callback): static
    {
        return new self($callback);
    }

    /**
     * @return Collection
     */
    public function collect(): Collection
    {
        return new Collection(iterator_to_array($this->getIterator()));
    }

    /**
     * @param iterable|Closure $iterable
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
