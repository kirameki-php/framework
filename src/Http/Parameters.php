<?php

namespace Kirameki\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class Parameters implements Countable, IteratorAggregate
{
    protected array $entries;

    public function all(): array
    {
        return $this->entries;
    }

    public function get(string $name): ?string
    {
        return $this->entries[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function set(string $name, $value): void
    {
        $this->entries[$name] = $value;
    }

    public function remove(string $name): void
    {
        unset($this->entries[$name]);
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->entries);
    }
}