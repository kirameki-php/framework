<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

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
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDest> $destModels
     * @return void
     */
    protected function setDestToSrc(Model $srcModel, ModelCollection $destModels): void
    {
        $srcModel->setRelation($this->getName(), $this->toRelationCollection($srcModel, $destModels));
        $this->setInverseRelations($srcModel, $destModels);
    }

    /**
     * @param TSrc $srcModel
     * @param iterable<int, TDest> $destModels
     * @return RelationCollection<TSrc, TDest>
     */
    protected function toRelationCollection(Model $srcModel, iterable $destModels): RelationCollection
    {
        return new RelationCollection($this, $srcModel, $this->getDestReflection(), $destModels);
    }

    /**
     * @param TSrc $srcModel
     * @param iterable<TDest> $destModels
     * @return void
     */
    protected function setInverseRelations(Model $srcModel, iterable $destModels): void
    {
        if ($inverse = $this->getInverseName()) {
            foreach ($destModels as $destModel) {
                $destModel->setRelation($inverse, $srcModel);
            }
        }
    }
}
