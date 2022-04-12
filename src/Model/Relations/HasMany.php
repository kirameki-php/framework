<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Model\ModelManager;
use Kirameki\Model\Reflection;

/**
 * @template TSrc of Model
 * @template TDst of Model
 * @template-extends Relation<TSrc, TDst>
 */
class HasMany extends Relation
{
    /**
     * @param ModelManager $manager
     * @param string $name
     * @param Reflection<TSrc> $srcReflection
     * @param class-string<TDst> $dstClass
     * @param array<string, string> $keyPairs should look like [$srcKeyName => $dstKeyName, ...]
     * @param string|null $inverse
     */
    public function __construct(ModelManager $manager, string $name, Reflection $srcReflection, string $dstClass, array $keyPairs = null, ?string $inverse = null)
    {
        parent::__construct($manager, $name, $srcReflection, $dstClass, $keyPairs, $inverse);
    }

    /**
     * @return array<string, string>
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
     * @param ModelCollection<int, TDst> $destModels
     * @return RelationCollection<TSrc, TDst>
     */
    protected function toRelationCollection(Model $srcModel, ModelCollection $destModels): RelationCollection
    {
        return new RelationCollection($this, $srcModel, $this->getDstReflection(), $destModels);
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDst> $destModels
     * @return void
     */
    protected function setInverseRelations(Model $srcModel, ModelCollection $destModels): void
    {
        if ($inverse = $this->getInverseName()) {
            foreach ($destModels as $destModel) {
                $destModel->setRelation($inverse, $srcModel);
            }
        }
    }
}
