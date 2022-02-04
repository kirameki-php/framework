<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\ModelCollection;
use Kirameki\Model\Model;
use Kirameki\Model\Reflection;

/**
 * @template TSrc of Model
 * @template TDest of Model
 * @extends ModelCollection<int, TDest>
 */
class RelationCollection extends ModelCollection
{
    /**
     * @var Relation<TSrc, TDest>
     */
    protected Relation $relation;

    /**
     * @var TSrc
     */
    protected Model $parent;

    /**
     * @var iterable<int, TDest>
     */
    protected iterable $items;

    /**
     * @param Relation<TSrc, TDest> $relation
     * @param TSrc $parent
     * @param Reflection<TDest> $reflection
     * @param iterable<int, TDest> $models
     */
    public function __construct(Relation $relation, Model $parent, Reflection $reflection, iterable $models)
    {
        parent::__construct($reflection, $models);
        $this->relation = $relation;
        $this->parent = $parent;

        if ($inverse = $relation->getInverseName()) {
            foreach ($models as $model) {
                $model->setRelation($inverse, $parent);
            }
        }
    }

    /**
     * @return Relation<TSrc, TDest>
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
     * @return TDest
     */
    public function make(array $properties = []): Model
    {
        $model = $this->reflection->makeModel($properties);
        return $this->setRelatedKeys($model);
    }

    /**
     * @param array<string, mixed> $properties
     * @return TDest
     */
    public function create(array $properties = []): Model
    {
        $model = $this->make($properties);
        $model->save();
        return $model;
    }

    /**
     * @template TRefModel of Model
     * @param TRefModel $model
     * @return TRefModel
     */
    protected function setRelatedKeys(Model $model): Model
    {
        $parentKeyName = $this->relation->getDestKeyName();
        $parentKey = $this->parent->getProperty($this->relation->getSrcKeyName());
        $model->setProperty($parentKeyName, $parentKey);
        return $model;
    }

    /**
     * @return void
     */
    public function saveAll(): void
    {
        foreach ($this->items as $item) {
            $item->save();
        }
    }
}
