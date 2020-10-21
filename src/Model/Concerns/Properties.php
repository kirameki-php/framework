<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Properties
{
    /**
     * @var array
     */
    protected array $originalProperties = [];

    /**
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        return array_keys(static::getReflection()->properties);
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        $properties = [];
        foreach ($this->getPropertyNames() as $name) {
            $properties[$name] = $this->getProperty($name);
        }
        return $properties;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties = [])
    {
        foreach ($properties as $name => $value) {
            $this->setProperty($name, $value);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getProperty(string $name)
    {
        if (array_key_exists($name, $this->resolved)) {
            return $this->resolved[$name];
        }

        $property = static::getReflection()->properties[$name];
        $value = $property->cast->get($this, $name, $this->originalProperties[$name]);
        $this->cacheResolved($name, $value);

        return $value;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setProperty(string $name, $value)
    {
        $this->markAsDirty($name, $this->getProperty($name));
        $this->cacheResolved($name, $value);
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isProperty(string $name): bool
    {
        return isset(static::getReflection()->properties[$name]);
    }

    /**
     * @return $this
     */
    protected function setDefaults()
    {
        $defined = static::getReflection()->properties;
        $unused = array_diff_key($defined, $this->originalProperties);
        foreach ($unused as $name => $property) {
            $this->setProperty($name, $property->default);
        }
        return $this;
    }
}
