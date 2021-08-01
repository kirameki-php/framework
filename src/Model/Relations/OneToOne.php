<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

abstract class OneToOne extends Relation
{
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
     * @param ModelCollection $targets
     * @return ModelCollection
     */
    public function loadOnCollection(ModelCollection $targets): ModelCollection
    {
        $mappedTargets = $targets->keyBy($this->getSrcKeyName())->compact();

        $relationModels = $this->buildQuery()->where($this->getDestKeyName(), $mappedTargets->keys())->all();
        $groupedRelationModels = $relationModels->groupBy($this->getDestKeyName());

        foreach ($groupedRelationModels as $keyName => $group) {
            if ($target = $mappedTargets->get($keyName)) {
                $relationModel = $group[0] ?? null;
                $target->setRelation($this->getName(), $relationModel);
                if ($relationModel !== null && $inverse = $this->getInverseName()) {
                    $relationModel->setRelation($inverse, $target);
                }
            }
        }

        return $relationModels;
    }
}
