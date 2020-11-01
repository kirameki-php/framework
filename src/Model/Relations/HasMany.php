<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Support\Collection;

class HasMany extends Relation
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= $this->getSrc()->primaryKey;
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= lcfirst(class_basename($this->getSrc()->class)).'Id';
    }

    /**
     * @param Model $target
     * @return Model|Model[]|RelationCollection
     */
    public function loadOnModel(Model $target)
    {
        $models = $this->buildQuery()
            ->where($this->getDestKeyName(), $this->getSrcKey($target))
            ->all();

        $collection = new RelationCollection($this, $target, $target->getReflection(), $models->toArray());

        $target->setRelation($this->getName(), $collection);

        return $collection;
    }

    /**
     * @param ModelCollection $targets
     * @return ModelCollection
     */
    public function loadOnCollection(ModelCollection $targets)
    {
        $mappedTargets = $targets->keyBy($this->getSrcKeyName())->compact();

        $relationModels = $this->buildQuery()->where($this->getDestKeyName(), $mappedTargets->keys())->all();
        $groupedRelationModels = $relationModels->groupBy($this->getDestKeyName());

        foreach ($mappedTargets->keys() as $key) {
            if ($target = $mappedTargets->get($key)) {
                $models = ($groupedRelationModels[$key] ?? new Collection())->toArray();
                $target->setRelation($this->getName(), new RelationCollection($this, $target, $this->getDest(), $models));
            }
        }

        return $relationModels;
    }
}
