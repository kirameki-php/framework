<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Support\Arr;
use Kirameki\Support\Collection;

/**
 * @template T
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
     * @param int|string $key
     * @return Collection
     */
    public function pluck(int|string $key): Collection
    {
        return $this->newCollection(Arr::pluck($this->items, $key));
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
     * @return int
     */
    public function deleteAll(): int
    {
        return $this->countBy(static fn(?Model $model) => ($model !== null && $model->delete()) ? 1 : 0);
    }
}
