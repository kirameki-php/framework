<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;
use Kirameki\Model\Relations\RelationCollection;
use Kirameki\Support\Arr;

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
     * @param string|string[] $relationNames
     * @return $this
     */
    public function preload(array $relationNames)
    {
        $this->preloadRecursive($this, $relationNames);
        return $this;
    }

    /**
     * @param Model|RelationCollection $target
     * @param array $names
     */
    protected function preloadRecursive(RelationCollection|Model $target, array $names)
    {
        if (Arr::isSequential($names)) {
            foreach ($names as $name) {
                $this->loadRelation($target, $name);
            }
        }
        else {
            foreach ($names as $name => $inner) {
                $model = $this->loadRelation($target, $name);
                $this->preloadRecursive($model, Arr::wrap($inner));
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRelation(string $name): bool
    {
        return isset(static::getReflection()->relations[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRelationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    /**
     * @return array<Model|RelationCollection>
     */
    public function getRelations(): array
    {
        return $this->relations ??= [];
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getRelation(string $name)
    {
        if (!array_key_exists($name, $this->relations)) {
            $this->loadRelation($this, $name);
        }
        return $this->relations[$name];
    }

    /**
     * @param Model|RelationCollection $target
     * @param string $name
     * @return Model|RelationCollection
     */
    protected function loadRelation($target, string $name)
    {
        if ($target instanceof RelationCollection) {
            $relation = $target->getModelReflection()->relations[$name];
            return $relation->loadOnCollection($target);
        }

        $relation = $target::getReflection()->relations[$name];
        return $relation->loadOnModel($target);
    }

    /**
     * @param string $name
     * @param $models
     * @return $this
     */
    public function setRelation(string $name, $models)
    {
        $this->relations[$name] = $models;
        return $this;
    }
}
