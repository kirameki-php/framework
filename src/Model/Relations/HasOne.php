<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @template-extends Relation<TSrc, TDest>
 */
class HasOne extends Relation
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
        $destModel = $destModels[0];
        $srcModel->setRelation($this->getName(), $destModel);
        $this->setInverseRelations($srcModel, $destModel);
    }

    /**
     * @param TSrc $srcModel
     * @param TDest $destModel
     * @return void
     */
    protected function setInverseRelations(Model $srcModel, Model $destModel): void
    {
        if ($inverse = $this->getInverseName()) {
            $destModel->setRelation($inverse, $srcModel);
        }
    }
}
