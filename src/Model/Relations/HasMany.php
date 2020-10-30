<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

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

        $collection->each(function(Model $model) use ($target) {
            if ($inverse = $this->getInverseName()) {
                $model->setRelation($inverse, $target);
            }
        });

        return $collection;
    }

    public function loadOnCollection(RelationCollection $targets)
    {

    }
}
