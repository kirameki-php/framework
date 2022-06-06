<?php declare(strict_types=1);

namespace Kirameki\Collections;

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
abstract class MutableCollection extends Sequence implements ArrayAccess
{
    /**
     * @param iterable<TKey, TValue>|null $items
     * @param bool|null $isList
     */
    public function __construct(iterable|null $items = null, ?bool $isList = null)
    {
        $array = Arr::from($items ?? []);
        $isList ??= array_is_list($array);
        parent::__construct($array, $isList);
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
     * @return $this
     */
    public function clear(): static
    {
        $this->items = [];
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
     * @param int $index
     * @param mixed $value
     * @return $this
     */
    public function insertAt(int $index, mixed $value): static
    {
        Arr::insertAt($this->items, $index, $value, $this->isList);
        return $this;
    }

    /**
     * @param int $index
     * @param array<TKey, TValue> $values
     * @return $this
     */
    public function insertManyAt(int $index, array $values): static
    {
        Arr::insertManyAt($this->items, $index, $values, $this->isList);
        return $this;
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
     * @param TKey $key
     * @return TValue|null
     */
    public function pull(int|string $key): mixed
    {
        return Arr::pull($this->items, $key, $this->isList);
    }

    /**
     * @template TDefault
     * @param TKey $key
     * @param TDefault $default
     * @return TValue|TDefault
     */
    public function pullOr(int|string $key, mixed $default): mixed
    {
        return Arr::pullOr($this->items, $key, $default, $this->isList);
    }

    /**
     * @param TKey $key
     * @return TValue
     */
    public function pullOrFail(int|string $key): mixed
    {
        return Arr::pullOrFail($this->items, $key, $this->isList);
    }

    /**
     * @param iterable<array-key> $keys
     * @return static
     */
    public function pullMany(iterable $keys): static
    {
        return $this->newInstance(Arr::pullMany($this->items, $keys, $this->isList));
    }

    /**
     * @param TValue $value
     * @param int|null $limit
     * @return array<int, array-key>
     */
    public function remove(mixed $value, ?int $limit = null): array
    {
        return Arr::remove($this->items, $value, $limit, $this->isList);
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
}
