<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

class HasOne extends Relation
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
        $model = $this->buildQuery()->one();
        $target->setRelation($this->name, $model);
        if ($model === null) {
            return;
        }
        if ($inverse = $this->getInverseName()) {
            $model->setRelation($inverse, $target);
        }
    }
}
