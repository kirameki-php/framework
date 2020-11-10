<?php

namespace Kirameki\Model;

use Kirameki\Support\Collection;

/**
 * @method Model[] toArray() { @see Collection::toArray() }
 */
class ModelCollection extends Collection
{
    /**
     * @var Reflection
     */
    protected Reflection $reflection;

    /**
     * @param Reflection $reflection
     * @param array<Model> $models
     */
    public function __construct(Reflection $reflection, array $models = [])
    {
        parent::__construct($models);
        $this->reflection = $reflection;
    }

    /**
     * @return Reflection
     */
    public function getModelReflection()
    {
        return $this->reflection;
    }

    /**
     * @return Collection
     */
    public function primaryKeys(): Collection
    {
        return $this->pluck($this->reflection->primaryKey);
    }

    /**
     * @return static
     */
    public function keyByPrimaryKey()
    {
        return $this->keyBy($this->reflection->primaryKey);
    }

    /**
     * @param int|string $key
     * @return Model|null
     */
    public function get($key)
    {
        return parent::get($key);
    }

    /**
     * @return int
     */
    public function deleteAll(): int
    {
        return $this->countBy(static fn(?Model $model) => ($model !== null && $model->delete()) ? 1 : 0);
    }
}