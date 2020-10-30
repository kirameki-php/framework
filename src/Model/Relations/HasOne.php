<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

class HasOne extends Relation
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

    public function loadOnCollection(RelationCollection $targets)
    {
        // TODO: Implement loadToCollection() method.
    }
}
