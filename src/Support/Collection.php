<?php declare(strict_types=1);

namespace Kirameki\Support;

use ArrayAccess;

/**
 * @template T
 */
class Collection extends Enumerable implements ArrayAccess
{
    /**
     * @param iterable|null $items
     */
    public function __construct(?iterable $items = null)
    {
        $this->items = $this->asArray($items ?? []);
    }

    /**
     * @param iterable|null $items
     * @return static
     */
    public function newInstance(?iterable $items = null): static
    {
        return new static($items);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            Assert::validKey($offset);
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * @param int $index
     * @param mixed $value
     * @return $this
     */
    public function insertAt(int $index, mixed $value): static
    {
        // Offset is off by one for negative indexes (Ex: -2 inserts at 3rd element from right).
        // So we add one to correct offset. If adding to one results in 0, we set it to max count
        // to put it at the end.
        if ($index < 0) {
            $index = $index === -1 ? $this->count() : $index + 1;
        }
        \array_splice($this->items, $index, 0, $value);
        return $this;
    }

    /**
     * @param int $size
     * @param mixed $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->newInstance(\array_pad($this->items, $size, $value));
    }

    /**
     * @return mixed
     */
    public function pop(): mixed
    {
        return \array_pop($this->items);
    }

    /**
     * @param int|string $key
     * @return T
     */
    public function pull(int|string $key): mixed
    {
        return Arr::pull($this->items, $key);
    }

    /**
     * @param T ...$value
     * @return $this
     */
    public function push(mixed ...$value): static
    {
        Arr::push($this->items, ...$value);
        return $this;
    }

    /**
     * @param T $value
     * @param int|null $limit
     * @return int
     */
    public function remove(mixed $value, ?int $limit = null): int
    {
        return Arr::remove($this->items, $value, $limit);
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function removeKey(int|string $key): bool
    {
        return Arr::removeKey($this->items, $key);
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @return $this
     */
    public function set(int|string $key, mixed $value): static
    {
        Arr::set($this->items, $key, $value);
        return $this;
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value): static
    {
        if (!$this->containsKey($key)) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * @return T
     */
    public function shift(): mixed
    {
        return \array_shift($this->items);
    }

    /**
     * @param mixed ...$value
     * @return $this
     */
    public function unshift(mixed ...$value): static
    {
        Arr::unshift($this->items, ...$value);
        return $this;
    }
}
