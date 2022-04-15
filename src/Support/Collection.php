<?php declare(strict_types=1);

namespace Kirameki\Support;

use ArrayAccess;
use Webmozart\Assert\Assert;

/**
 * @template TKey of array-key|class-string
 * @template TValue
 *
 * @extends Sequence<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 *
 * @property array<TKey, TValue> $items
 */
class Collection extends Sequence implements ArrayAccess
{
    /**
     * @param iterable<TKey, TValue>|null $items
     */
    public function __construct(iterable|null $items = null)
    {
        parent::__construct(Arr::from($items ?? []));
    }

    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param TKey|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            Assert::validArrayKey($offset);
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
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
     * @return TValue|null
     */
    public function pop(): mixed
    {
        return Arr::pop($this->items);
    }

    /**
     * @param int $amount
     * @return static<int, TValue>
     */
    public function popMany(int $amount): Collection
    {
        return $this->newInstance(Arr::popMany($this->items, $amount));
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function pull(int|string $key): mixed
    {
        return Arr::pull($this->items, $key);
    }

    /**
     * @param TValue ...$value
     * @return $this
     */
    public function push(mixed ...$value): static
    {
        Arr::push($this->items, ...$value);
        return $this;
    }

    /**
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, array-key>
     */
    public function remove(mixed $value, ?int $limit = null): array
    {
        return Arr::remove($this->items, $value, $limit);
    }

    /**
     * @param TKey $key
     * @return bool
     */
    public function removeKey(int|string $key): bool
    {
        return Arr::removeKey($this->items, $key);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function set(int|string $key, mixed $value): static
    {
        Arr::set($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @param bool|null $result
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value, bool &$result = null): static
    {
        $result !== null
            ? Arr::setIfExists($this->items, $key, $value, $result)
            : Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @param TKey $key
     * @param TValue $value
     * @param bool|null $result
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value, bool &$result = null): static
    {
        $result !== null
            ? Arr::setIfNotExists($this->items, $key, $value, $result)
            : Arr::setIfNotExists($this->items, $key, $value);
        return $this;
    }

    /**
     * @return TValue|null
     */
    public function shift(): mixed
    {
        return Arr::shift($this->items);
    }

    /**
     * @param int $amount
     * @return static<int, TValue>
     */
    public function shiftMany(int $amount): Collection
    {
        return $this->newInstance(Arr::shiftMany($this->items, $amount));
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
