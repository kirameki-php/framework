<?php declare(strict_types=1);

namespace Kirameki\Support;

use ArrayAccess;
use Kirameki\Collections\Arr;
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
     * @param TValue ...$value
     * @return $this
     */
    public function append(mixed ...$value): static
    {
        Arr::append($this->items, ...$value);
        return $this;
    }

    /**
     * @param int|string $key
     * @return TValue|null
     */
    public function get(int|string $key): mixed
    {
        return Arr::get($this, $key);
    }

    /**
     * @template TDefault
     * @param int|string $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function getOr(int|string $key, mixed $default): mixed
    {
        return Arr::getOr($this, $key, $default);
    }

    /**
     * @param int|string $key
     * @return TValue
     */
    public function getOrFail(int|string $key): mixed
    {
        return Arr::getOrFail($this, $key);
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
        return $this->newInstance(Arr::pad($this, $size, $value));
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
     * @return static
     */
    public function popMany(int $amount): static
    {
        return $this->newInstance(Arr::popMany($this->items, $amount));
    }

    /**
     * @param mixed ...$value
     * @return $this
     */
    public function prepend(mixed ...$value): static
    {
        Arr::prepend($this->items, ...$value);
        return $this;
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
     * @template TDefault
     * @param TKey $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function pullOr(int|string $key, mixed $default): mixed
    {
        return Arr::pullOr($this->items, $key, $default);
    }

    /**
     * @param TKey $key
     * @return TValue
     */
    public function pullOrFail(int|string $key): mixed
    {
        return Arr::pullOrFail($this->items, $key);
    }

    /**
     * @param int|string ...$key
     * @return static
     */
    public function pullMany(int|string ...$key): static
    {
        return $this->newInstance(Arr::pullMany($this->items, ...$key));
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
    public function setIfExists(int|string $key, mixed $value, ?bool &$result = null): static
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
    public function setIfNotExists(int|string $key, mixed $value, ?bool &$result = null): static
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
     * @return static
     */
    public function shiftMany(int $amount): static
    {
        return $this->newInstance(Arr::shiftMany($this->items, $amount));
    }

    /**
     * @return Sequence<TKey, TValue>
     */
    public function toSequence(): Sequence
    {
        return new Sequence($this->toArray());
    }
}
