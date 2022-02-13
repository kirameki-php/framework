<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template TSrc of Model
 * @template TDst of Model
 * @template-extends Relation<TSrc, TDst>
 */
class HasMany extends Relation
{
    /**
     * @return non-empty-array<non-empty-string, string>
     */
    protected function guessKeyPairs(): array
    {
        $srcKeyName = $this->getSrcReflection()->primaryKey;
        $dstKeyName = lcfirst(class_basename($this->getSrcReflection()->class)).'Id';
        return [$srcKeyName => $dstKeyName];
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDst> $dstModels
     * @return void
     */
    protected function setDstToSrc(Model $srcModel, ModelCollection $dstModels): void
    {
        $srcModel->setRelation($this->getName(), $this->toRelationCollection($srcModel, $dstModels));
        $this->setInverseRelations($srcModel, $dstModels);
    }

    /**
     * @param TSrc $srcModel
     * @param iterable<int, TDst> $destModels
     * @return RelationCollection<TSrc, TDst>
     */
    protected function toRelationCollection(Model $srcModel, iterable $destModels): RelationCollection
    {
        return new RelationCollection($this, $srcModel, $this->getDstReflection(), $destModels);
    }

    /**
     * @param TSrc $srcModel
     * @param iterable<TDst> $destModels
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
