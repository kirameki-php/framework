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
    public function __construct(iterable|null $items = null)
    {
        $items ??= [];
        $this->items = $this->asArray($items);
    }

    /**
     * @param iterable|null $items
     * @return static
     */
    public function newInstance(?iterable $items = null): static
    {
        return new self($items);
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
        if ($offset === null) {
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
    public function insertAt(int $index, mixed ...$value): static
    {
        Arr::insertAt($this->items, $index, ...$value);
        return $this;
    }

    /**
     * @param int $size
     * @param mixed $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return $this->newInstance(Arr::pad($this->items, $size, $value));
    }

    /**
     * @return T
     */
    public function pop(): mixed
    {
        return Arr::pop($this->items);
    }

    /**
     * @param int $amount
     * @return Collection<T>
     */
    public function popMany(int $amount): Collection
    {
        return $this->newCollection(Arr::popMany($this->items, $amount));
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
     * @param bool|null $result
     * @return $this
     */
    public function setIfAlreadyExists(int|string $key, mixed $value, bool &$result = null): static
    {
        $result = false;
        if ($this->containsKey($key)) {
            $this->set($key, $value);
            $result = true;
        }
        return $this;
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @param bool|null $result
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value, bool &$result = null): static
    {
        $result = false;
        if (!$this->containsKey($key)) {
            $this->set($key, $value);
            $result = true;
        }
        return $this;
    }

    /**
     * @return T
     */
    public function shift(): mixed
    {
        return Arr::shift($this->items);
    }

    /**
     * @param int $amount
     * @return Collection<T>
     */
    public function shiftMany(int $amount): Collection
    {
        return $this->newCollection(Arr::shiftMany($this->items, $amount));
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
