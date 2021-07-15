<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Kirameki\Support\Arr;

class RequestData implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @param array $inputs
     */
    public function __construct(array $inputs = [])
    {
        $this->data = $inputs;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->raws[$offset]);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->delete($offset);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function get(string $name): ?string
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function set(string $name, $value): static
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function delete(string $name): static
    {
        unset($this->data[$name]);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function merge(array $data): static
    {
        Arr::mergeRecursive($this->data, $data);
        return $this;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
