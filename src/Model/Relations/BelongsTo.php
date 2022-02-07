<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @template-extends Relation<TSrc, TDest>
 */
class BelongsTo extends Relation
{
    /**
     * @return string
     */
    public function getSrcKeyName(): string
    {
        return $this->srcKey ??= $this->guessSrcKeyName();
    }

    /**
     * @return string
     */
    protected function guessSrcKeyName(): string
    {
        return lcfirst(class_basename($this->getDestReflection()->class)).'Id';
    }

    /**
     * @return string
     */
    public function getDestKeyName(): string
    {
        return $this->destKey ??= $this->getDestReflection()->primaryKey;
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDest> $destModels
     * @return void
     */
    protected function setDestToSrc(Model $srcModel, ModelCollection $destModels): void
    {
        $srcModel->setRelation($this->getName(), $destModels[0]);
    }
}
