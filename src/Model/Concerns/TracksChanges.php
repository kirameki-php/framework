<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait TracksChanges
{
    /**
     * Stores initial values for properties that were changed.
     *
     * @var array
     */
    protected array $changedProperties = [];

    /**
     * Stores previous value of properties.
     * It will get cleared when the model is saved.
     *
     * @var array
     */
    protected array $previousProperties = [];

    /**
     * @param string $name
     * @param $oldValue
     * @return $this
     */
    protected function markAsDirty(string $name, $oldValue)
    {
        if (!array_key_exists($name, $this->changedProperties)) {
            $this->changedProperties[$name] = $oldValue;
        }
        $this->previousProperties[$name] = $oldValue;
        return $this;
    }

    /**
     * @param string|null $name
     * @return mixed
     */
    public function getInitialProperty(string $name = null): mixed
    {
        return array_key_exists($name, $this->changedProperties)
            ? $this->changedProperties[$name]
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
     * @return mixed|null
     */
    public function getPreviousProperty(string $name)
    {
        return $this->previousProperties[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getPreviousProperties(): array
    {
        return $this->previousProperties;
    }

    /**
     * @param string|null $name
     * @return bool
     */
    public function isDirty(string $name = null): bool
    {
        return $name !== null
            ? array_key_exists($name, $this->previousProperties)
            : !empty($this->previousProperties);
    }

    /**
     * @return array
     */
    public function getDirtyProperties(): array
    {
        $props = [];
        foreach ($this->previousProperties as $name => $_) {
            $props[$name] = $this->getProperty($name);
        }
        return $props;
    }

    /**
     * @return $this
     */
    public function clearDirty()
    {
        $this->previousProperties = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function clearChanges()
    {
        $this->changedProperties = [];
        $this->previousProperties = [];
        return $this;
    }
}
