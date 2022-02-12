<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Properties
{
    /**
     * This has raw values that come directly from the database.
     * For example, the datetime values are stored as string
     * and will not cast until it is actually used.
     *
     * @var array
     */
    protected array $_persistedProperties = [];

    /**
     * Stores and caches properties that were casted.
     * Casting occurs when a value is set or when a value is called though get.
     *
     * @var array
     */
    protected array $_resolvedProperties = [];

    /**
     * Stores initial values for properties that were changed.
     *
     * @var array
     */
    protected array $_changedProperties = [];

    /**
     * Stores previous value of properties.
     * It will get cleared when the model is saved.
     *
     * @var array
     */
    protected array $_previousProperties = [];

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
    public function setProperties(array $properties = []): static
    {
        foreach ($properties as $name => $value) {
            $this->setProperty($name, $value);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getProperty(string $name): mixed
    {
        if (array_key_exists($name, $this->_resolvedProperties)) {
            return $this->_resolvedProperties[$name];
        }

        $property = static::getReflection()->properties[$name];

        $value = isset($this->_persistedProperties[$name])
            ? $property->cast->get($this, $name, $this->_persistedProperties[$name])
            : null;

        $this->cacheResolved($name, $value);

        return $value;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setProperty(string $name, $value): static
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
     * @param array $properties
     * @return $this
     */
    protected function setPersistedProperties(array $properties): static
    {
        $this->_persistedProperties = $properties;
        return $this;
    }

    /**
     * @return $this
     */
    protected function setDefaultProperties(array $excludes = []): static
    {
        $defined = static::getReflection()->properties;
        $unused = array_diff_key($defined, $excludes);
        foreach ($unused as $name => $property) {
            $this->setProperty($name, $property->default);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    protected function cacheResolved(string $name, $value): static
    {
        $this->_resolvedProperties[$name] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function clearResultCache(): static
    {
        $this->_resolvedProperties = [];
        return $this;
    }

    /**
     * @param string|null $name
     * @return bool
     */
    public function isResultCached(string $name = null): bool
    {
        return $name !== null
            ? array_key_exists($name, $this->_resolvedProperties)
            : !empty($this->_resolvedProperties);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getInitialProperty(string $name): mixed
    {
        return array_key_exists($name, $this->_changedProperties)
            ? $this->_changedProperties[$name]
            : $this->getProperty($name);
    }

    /**
     * @return array
     */
    public function getInitialProperties(): array
    {
        $props = [];
        foreach ($this->getPropertyNames() as $name) {
            $props[$name] = $this->getInitialProperty($name);
        }
        return $props;
    }

    /**
     * @param string $name
     * @param mixed $oldValue
     * @return $this
     */
    protected function markAsDirty(string $name, mixed $oldValue): static
    {
        if (!array_key_exists($name, $this->_changedProperties)) {
            $this->_changedProperties[$name] = $oldValue;
        }
        $this->_previousProperties[$name] = $oldValue;
        return $this;
    }

    /**
     * @param string|null $name
     * @return bool
     */
    public function isDirty(string $name = null): bool
    {
        return $name !== null
            ? array_key_exists($name, $this->_previousProperties)
            : !empty($this->_previousProperties);
    }

    /**
     * @return array
     */
    public function getDirtyProperties(): array
    {
        $props = [];
        foreach ($this->_previousProperties as $name => $_) {
            $props[$name] = $this->getProperty($name);
        }
        return $props;
    }

    /**
     * @return $this
     */
    protected function clearDirty(): static
    {
        $this->_previousProperties = [];
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getPreviousProperty(string $name): mixed
    {
        return $this->_previousProperties[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getPreviousProperties(): array
    {
        return $this->_previousProperties;
    }

    /**
     * @param string|null $name
     * @return bool
     */
    public function wasChanged(string $name = null): bool
    {
        return $name !== null
            ? array_key_exists($name, $this->_changedProperties)
            : !empty($this->_changedProperties);
    }

    /**
     * @return $this
     */
    public function clearChanges(): static
    {
        $this->_changedProperties = [];
        $this->_previousProperties = [];
        return $this;
    }
}
