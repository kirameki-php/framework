<?php

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;

class BelongsTo extends Relation
{
    /**
     * @return string
     */
    public function getSrcKey(): string
    {
        return $this->srcKey ??= lcfirst(class_basename($this->getDest()->class)).'Id';
    }

    /**
     * @return string
     */
    public function getDestKey(): string
    {
        return $this->destKey ??= $this->getDest()->primaryKey;
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
