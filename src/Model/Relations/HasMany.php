<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

class HasMany extends Relation
{
    /**
     * @return string
     */
    public function getSrcKey(): string
    {
        return $this->srcKey ??= $this->getSrc()->primaryKey;
    }

    /**
     * @return string
     */
    public function getDestKey(): string
    {
        return $this->destKey ??= lcfirst(class_basename($this->getSrc()->class)).'Id';
    }

    /**
     * @param Model $target
     */
    public function loadTo(Model $target): void
    {
        $models = $this->buildQuery()->all();
        $target->setRelation($this->getName(), $models);
        $models->each(function(Model $model) use ($target) {
            if ($inverse = $this->getInverseName()) {
                $model->setRelation($inverse, $target);
            }
        });
    }
}
