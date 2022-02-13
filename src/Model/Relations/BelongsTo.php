<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;

/**
 * @template TSrc of Model
 * @template TDst of Model
 * @template-extends Relation<TSrc, TDst>
 */
class BelongsTo extends Relation
{
    /**
     * @return non-empty-array<non-empty-string, non-empty-string>
     */
    protected function guessKeyPairs(): array
    {
        $srcKeyName = $this->getDstReflection()->primaryKey;
        $dstKeyName = lcfirst(class_basename($this->getDstReflection()->class)).'Id';
        return [$srcKeyName => $dstKeyName];
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDst> $dstModels
     * @return void
     */
    protected function setDstToSrc(Model $srcModel, ModelCollection $dstModels): void
    {
        $srcModel->setRelation($this->getName(), $dstModels[0]);
    }
}
