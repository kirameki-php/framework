<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template TSrc of Model
 * @template TDst of Model
 * @template-extends Relation<TSrc, TDst>
 */
class HasOne extends Relation
{
    /**
     * @return non-empty-array<non-empty-string, non-empty-string>
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
        $destModel = $dstModels[0];
        $srcModel->setRelation($this->getName(), $destModel);
        $this->setInverseRelations($srcModel, $destModel);
    }

    /**
     * @param TSrc $srcModel
     * @param TDst $destModel
     * @return void
     */
    protected function setInverseRelations(Model $srcModel, Model $destModel): void
    {
        if ($inverse = $this->getInverseName()) {
            $destModel->setRelation($inverse, $srcModel);
        }
    }
}
