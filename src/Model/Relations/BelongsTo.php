<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

class BelongsTo extends Relation
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= lcfirst(class_basename($this->getDest()->class)).'Id';
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= $this->getDest()->primaryKey;
    }

    /**
     * @param Model $target
     * @return Model
     */
    public function loadOnModel(Model $target): Model
    {
        $model = $this->buildQuery()
            ->where($this->getDestKeyName(), $this->getSrcKey($target))
            ->first();

        $target->setRelation($this->name, $model);

        if ($model !== null) {
            if ($inverse = $this->getInverseName()) {
                $model->setRelation($inverse, $target);
            }
        }

        return $model;
    }

    /**
     * @param RelationCollection $targets
     * @return RelationCollection
     */
    public function loadOnCollection($targets)
    {
        $mappedParents = $targets
            ->keyBy($targets->getRelation()->getDestKeyName())
            ->compact();

        $models = $this->buildQuery()
            ->where($this->getDestKeyName(), $this->getSrcKeys($targets)->compact())
            ->all()
            ->groupBy($this->getDestKeyName());


    }
}
