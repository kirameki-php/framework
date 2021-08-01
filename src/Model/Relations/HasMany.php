<?php declare(strict_types=1);

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
        return $this->srcKey ??= $this->getSrcReflection()->primaryKey;
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= lcfirst(class_basename($this->getSrcReflection()->class)).'Id';
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
    public function loadOnCollection(ModelCollection $targets): ModelCollection
    {
        $mappedTargets = $targets->keyBy($this->getSrcKeyName())->compact();

        $relationName = $this->getName();
        $relationModels = $this->buildQuery()->where($this->getDestKeyName(), $mappedTargets->keys())->all();
        $groupedRelationModels = $relationModels->groupBy($this->getDestKeyName());
        $destReflection = $this->getDestReflection();

        foreach ($mappedTargets->keys() as $key) {
            if ($target = $mappedTargets->get($key)) {
                $models = ($groupedRelationModels[$key] ?? new Collection())->toArray();
                $target->setRelation($relationName, new RelationCollection($this, $target, $destReflection, $models));
            }
        }

        return $relationModels;
    }
}
