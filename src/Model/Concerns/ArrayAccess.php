<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait ArrayAccess
{
    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getProperty($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setProperty($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_persistedProperties[$offset])
            || isset($this->_resolvedProperties[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->setProperty($offset, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAssoc(): array
    {
        $array = [];
        foreach ($this->getPropertyNames() as $name) {
            $array[$name] = $this->getProperty($name);
        }
        return $array;
    }
}
