<?php

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
    public function offsetSet(mixed $offset, mixed $value)
    {
        $this->setProperty($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->persistedProperties[$offset])
            || isset($this->resolvedProperties[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset)
    {
        $this->setProperty($offset, null);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->getPropertyNames() as $name) {
            $array[$name] = $this->getProperty($name);
        }
        return $array;
    }
}
