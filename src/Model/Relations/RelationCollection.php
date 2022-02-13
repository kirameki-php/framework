<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\ModelCollection;
use Kirameki\Model\Model;
use Kirameki\Model\Reflection;

/**
 * @template TSrc of Model
 * @template TDst of Model
 * @extends ModelCollection<int, TDst>
 */
class RelationCollection extends ModelCollection
{
    /**
     * @var Relation<TSrc, TDst>
     */
    protected Relation $relation;

    /**
     * @var TSrc
     */
    protected Model $srcModel;

    /**
     * @var iterable<int, TDst>
     */
    protected iterable $items;

    /**
     * @param Relation<TSrc, TDst> $relation
     * @param TSrc $srcModel
     * @param Reflection<TDst> $dstReflection
     * @param iterable<int, TDst> $dstModels
     */
    public function __construct(Relation $relation, Model $srcModel, Reflection $dstReflection, iterable $dstModels)
    {
        parent::__construct($dstReflection, $dstModels);
        $this->relation = $relation;
        $this->srcModel = $srcModel;
   }

    /**
     * @return Relation<TSrc, TDst>
     */
    public function getRelation(): Relation
    {
        return $this->relation;
    }

    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->reflection->class;
    }

    /**
     * @param array<string, mixed> $properties
     * @return TDst
     */
    public function make(array $properties = []): Model
    {
        $model = $this->reflection->makeModel($properties);
        return $this->setRelatedKeys($model);
    }

    /**
     * @param array<string, mixed> $properties
     * @return TDst
     */
    public function create(array $properties = []): Model
    {
        return $this->make($properties)->save();
    }

    /**
     * @return void
     */
    public function save(): void
    {
        foreach ($this->items as $item) {
            $item->save();
        }
    }

    /**
     * @template UDst of Model
     * @param UDst $dstModel
     * @return UDst
     */
    protected function setRelatedKeys(Model $dstModel): Model
    {
        foreach ($this->relation->getKeyPairs() as $srcKeyName => $dstKeyName) {
            $srcKey = $this->srcModel->getProperty($srcKeyName);
            $dstModel->setProperty($dstKeyName, $srcKey);
        }
        return $dstModel;
    }
}
