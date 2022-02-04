<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Support\Collection;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @template-extends Relation<TSrc, TDest>
 */
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
        return $this->destKey ??= $this->guessDestKeyName();
    }

    /**
     * @return string
     */
    public function guessDestKeyName(): string
    {
        return lcfirst(class_basename($this->getSrcReflection()->class)).'Id';
    }

    /**
     * @param TSrc $target
     * @return TDest|RelationCollection<TSrc, TDest>
     */
    public function loadOnModel(Model $target): Model|RelationCollection
    {
        $models = $this->buildQuery()
            ->where($this->getDestKeyName(), $this->getSrcKey($target))
            ->all();

        $collection = new RelationCollection($this, $target, $this->getDestReflection(), $models->toArray());

        $target->setRelation($this->getName(), $collection);

        return $collection;
    }

    /**
     * @param ModelCollection<int, TSrc> $targets
     * @return RelationCollection<TSrc, TDest>
     */
    public function loadOnCollection(ModelCollection $targets): RelationCollection
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
