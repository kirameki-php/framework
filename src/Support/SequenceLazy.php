<?php declare(strict_types=1);

namespace Kirameki\Support;

use Closure;
use Iterator;
use Webmozart\Assert\Assert;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Sequence<TKey, TValue>
 */
class SequenceLazy extends Sequence
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
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Iterator
    {
        return ($this->iteratorCaller)();
    }

    /**
     * @inheritDoc
     */
    public function chunk(int $size): static
    {
        Assert::positiveInteger($size);

        return new static(function () use ($size) {
            $remaining = $size;
            $chunk = [];
            foreach ($this as $key => $val) {
                $chunk[$key] = $val;
                $remaining--;
                if ($remaining === 0) {
                    yield $chunk;
                    $remaining = $size;
                    $chunk = [];
                }
            }
            if (count($chunk) > 0) {
                yield $chunk;
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function compact(int $depth = 1): static
    {
        return new static(function() use ($depth) {
            $result = [];
            foreach ($this as $key => $val) {
                if (is_iterable($val) && $depth > 1) {
                    $val = Arr::compact($val, $depth - 1); /** @phpstan-ignore-line */
                }
                if ($val !== null) {
                    $result[$key] = $val;
                    yield $key => $val;
                }
            }
            return $result;
        });
    }

    /**
     * @inheritDoc
     */
    public function drop(int $amount): static
    {
        if ($amount < 0) {
            return parent::drop($amount);
        }

        return new static(function () use ($amount) {
            $count = 0;
            foreach ($this as $key => $item) {
                if ($count > $amount) {
                    yield $key => $item;
                }
                ++$count;
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function dropUntil(callable $condition): static
    {
        return new static(function () use ($condition) {
            $drop = true;
            foreach ($this as $key => $item) {
                if ($drop && $condition($item, $key)) {
                    $drop = false;
                } else {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function dropWhile(callable $condition): static
    {
        return new static(function () use ($condition) {
            $drop = true;
            foreach ($this as $key => $item) {
                if ($drop && !$condition($item, $key)) {
                    $drop = false;
                } else {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function filter(callable $condition): static
    {
        return new static(function () use ($condition) {
            foreach ($this as $key => $item) {
                if ($condition($item, $key)) {
                    yield $key => $item;
                }
            }
        });
    }

    /**
     * @return static
     */
    public function keys(): static
    {
        return new static(function () {
            foreach ($this as $key => $item) {
                yield $key;
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function map(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $item) {
                yield $key => $callback($item, $key);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function take(int $amount): static
    {
        if ($amount < 0) {
            return parent::take($amount);
        }

        return new static(function () use ($amount) {
            $count = 0;
            foreach ($this as $key => $item) {
                if ($count > $amount) {
                    break;
                }
                yield $key => $item;
                ++$count;
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function takeUntil(callable $condition): static
    {
        return new static(function () use ($condition) {
            foreach ($this as $key => $item) {
                if (!$condition($item, $key)) {
                    yield $key => $item;
                } else {
                    break;
                }
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function takeWhile(callable $condition): static
    {
        return new static(function () use ($condition) {
            foreach ($this as $key => $item) {
                if ($condition($item, $key)) {
                    yield $key => $item;
                } else {
                    break;
                }
            }
        });
    }

    /**
     * @return Sequence<TKey, TValue>
     */
    public function eager(): Sequence
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
