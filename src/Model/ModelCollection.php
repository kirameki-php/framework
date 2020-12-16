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
     * @param iterable<Model> $models
     */
    public function __construct(Reflection $reflection, iterable $models = [])
    {
        parent::__construct($models);
        $this->reflection = $reflection;
    }

    /**
     * @return Reflection
     */
    public function getModelReflection(): Reflection
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
    public function keyByPrimaryKey(): static
    {
        return $this->keyBy($this->reflection->primaryKey);
    }

    /**
     * @param int|string $key
     * @return Model|null
     */
    public function get(int|string $key): ?Model
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
