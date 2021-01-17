<?php

namespace Kirameki\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Kirameki\Support\Arr;
use Kirameki\Support\Json;

class Parameters implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array
     */
    protected array $entries;

    /**
     * @return static
     */
    public static function blank(): static
    {
        return new static;
    }

    /**
     * @param string $type
     * @param string $input
     * @return static
     */
    public static function fromMediaType(string $type, string $input): static
    {
        /** @var HttpManager $httpManager */
        $httpManager = app()->get(HttpManager::class);
        $data = $httpManager->getContentHandler($type)->receive($input);
        return new static($data);
    }

    /**
     * @param array $entries
     */
    public function __construct(array $entries = [])
    {
        $this->entries = $entries;
    }

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

    public function set(string $name, $value): static
    {
        $this->entries[$name] = $value;
        return $this;
    }

    public function remove(string $name): static
    {
        unset($this->entries[$name]);
        return $this;
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function merge(iterable $entries): static
    {
        $this->entries = array_merge_recursive($this->entries, Arr::from($entries));
        return $this;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->entries);
    }

    public function jsonSerialize(): string
    {
        return Json::encode($this->entries);
    }
}
