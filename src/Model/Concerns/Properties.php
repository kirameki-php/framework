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
    protected array $rawProperties = [];

    /**
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        return array_keys($this->rawProperties);
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
     * @param string $name
     * @return mixed|null
     */
    public function getProperty(string $name)
    {
        if (array_key_exists($name, $this->resolved)) {
            return $this->resolved[$name];
        }

        $property = static::getReflection()->properties[$name];
        $value = $property->cast->get($this, $name, $this->rawProperties[$name]);
        $this->cacheResult($name, $value);

        return $value;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setProperty(string $name, $value)
    {
        $this->cacheResult($name, $value);
        $property = static::getReflection()->properties[$name];
        $rawValue = $property->cast->set($this, $name, $value);
        $this->rawProperties[$name] = $rawValue;

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
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes = [])
    {
        foreach ($attributes as $name => $attribute) {
            $this->setProperty($name, $attribute);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function fillDefaults()
    {
        $defined = static::getReflection()->properties;
        $unused = array_diff_key($defined, $this->rawProperties);
        foreach ($unused as $name => $property) {
            $this->setProperty($name, $property->default);
        }
        return $this;
    }
}
