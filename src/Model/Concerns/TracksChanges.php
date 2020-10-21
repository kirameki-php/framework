<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait TracksChanges
{
    /**
     * @var array
     */
    protected array $changedProperties = [];

    /**
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
        $this->changedProperties[$name] ??= $oldValue;
        $this->previousProperties[$name] = $oldValue;
        return $this;
    }

    /**
     * @param string|null $name
     * @return array
     */
    public function getInitialProperty(string $name = null): array
    {
        return $this->changedProperties[$name] ?? $this->getProperty($name);
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
     * @return $this
     */
    public function clearDirty()
    {
        $this->previousProperties = [];
        return $this;
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
     * @return array
     */
    public function getDirtyProperties()
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
    public function clearChanges()
    {
        $this->changedProperties = [];
        $this->previousProperties = [];
        return $this;
    }
}
