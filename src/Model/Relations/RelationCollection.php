<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\ModelCollection;
use Kirameki\Model\Model;
use Kirameki\Model\Reflection;

class RelationCollection extends ModelCollection
{
    /**
     * @var Relation
     */
    protected Relation $relation;

    /**
     * @var Model
     */
    protected Model $parent;

    /**
     * @var Model[]
     */
    protected iterable $items;

    /**
     * @param Relation $relation
     * @param Model $parent
     * @param Reflection $reflection
     * @param Model[] $models
     */
    public function __construct(Relation $relation, Model $parent, Reflection $reflection, array $models = [])
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
     * @return Relation
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
     * @param array $properties
     * @return Model
     */
    public function make(array $properties = []): Model
    {
        $model = $this->reflection->makeModel($properties);
        return $this->setRelatedKeys($model);
    }

    /**
     * @param array $properties
     * @return Model
     */
    public function create(array $properties = []): Model
    {
        $model = $this->make($properties);
        $model->save();
        return $model;
    }

    /**
     * @param Model $model
     * @return Model
     */
    protected function setRelatedKeys(Model $model): Model
    {
        if ($this->parent) {
            $parentKeyName = $this->relation->getDestKeyName();
            $parentKey = $this->parent->getProperty($this->relation->getSrcKeyName());
            $model->setProperty($parentKeyName, $parentKey);
        }
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
