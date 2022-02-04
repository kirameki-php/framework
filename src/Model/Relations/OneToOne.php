<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @template-extends Relation<TSrc, TDest>
 */
abstract class OneToOne extends Relation
{
    /**
     * @param TSrc $target
     * @return TDest|null
     */
    public function loadOnModel(Model $target): ?Model
    {
        $model = $this->buildQuery()
            ->where($this->getDestKeyName(), $this->getSrcKey($target))
            ->first();

        if ($model === null) {
            return null;
        }

        $target->setRelation($this->name, $model);

        if ($model !== null) {
            if ($inverse = $this->getInverseName()) {
                $model->setRelation($inverse, $target);
            }
        }

        return $model;
    }

    /**
     * @param ModelCollection<int, TSrc> $targets
     * @return RelationCollection<TSrc, TDest>
     */
    public function loadOnCollection(ModelCollection $targets): RelationCollection
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
