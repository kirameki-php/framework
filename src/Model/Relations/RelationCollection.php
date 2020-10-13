<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\ModelCollection;
use Kirameki\Model\ModelManager;
use Kirameki\Model\Model;
use Kirameki\Model\Reflection;

class RelationCollection extends ModelCollection
{
    /**
     * @var Relation
     */
    protected Relation $relation;

    /**
     * @var Model
     */
    protected Model $parent;

    /**
     * @param Relation $relation
     * @param Model $parent
     * @param Reflection $reflection
     * @param array $models
     */
    public function __construct(Relation $relation, Model $parent, Reflection $reflection, array $models = [])
    {
        parent::__construct($reflection, $models);
        $this->relation = $relation;
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->reflection->class;
    }

    /**
     * @return Reflection
     */
    public function getModelReflection(): Reflection
    {
        /** @var ModelManager $manager */
        $manager = app()->get(ModelManager::class);
        return $manager->reflect($this->getModelClass());
    }

    /**
     * @param array $properties
     * @return Model
     */
    public function make(array $properties = []): Model
    {
        $model = $this->reflection->makeModel($properties);
        return $this->setRelatedKeys($model);
    }

    /**
     * @param array $properties
     * @return Model
     */
    public function create(array $properties = []): Model
    {
        $model = $this->make($properties);
        $model->save();
        return $model;
    }

    /**
     * @param Model $model
     * @return Model
     */
    protected function setRelatedKeys(Model $model)
    {
        if ($this->parent) {
            $parentKeyName = $this->relation->getDestKey();
            $parentKey = $this->parent->getProperty($this->relation->getSrcKey());
            $model->setProperty($parentKeyName, $parentKey);
        }
        return $model;
    }
}
