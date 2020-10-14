<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Relations
{
    /**
     * @var array
     */
    protected array $relations;

    /**
     * @param string $name
     * @return bool
     */
    public function isRelation(string $name): bool
    {
        return isset(static::getReflection()->relations[$name]);
    }

    /**
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getRelation(string $name)
    {
        return $this->resolved[$name] ?? $this->relations[$name] ?? null;
    }

    /**
     * @param string $name
     * @return $this
     */
    protected function loadRelation(string $name)
    {
        $reflection = static::getReflection();
        $relation = $reflection->relations[$name];
        return $this;
    }

    /**
     * @param string $name
     * @param $models
     * @return $this
     */
    public function setRelation(string $name, $models)
    {
        $this->resolved[$name] = $models;
        $this->relations[$name] = $models;
        return $this;
    }
}
