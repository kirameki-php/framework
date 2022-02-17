<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Support\Arr;
use Kirameki\Support\Collection;

/**
 * @template TKey of array-key
 * @template TModel of Model
 * @extends Collection<TKey, TModel>
 */
class ModelCollection extends Collection
{
    /**
     * @var Reflection<TModel>
     */
    protected Reflection $reflection;

    /**
     * @param Reflection<TModel> $reflection
     * @param iterable<TKey, TModel> $models
     */
    public function __construct(Reflection $reflection, iterable $models = [])
    {
        parent::__construct($models);
        $this->reflection = $reflection;
    }

    /**
     * @param iterable<TKey, TModel>|null $items
     * @return static
     */
    public function newInstance(?iterable $items = null): static
    {
        return new static($this->reflection, $items);
    }

    /**
     * @return Reflection<TModel>
     */
    public function getModelReflection(): Reflection
    {
        return $this->reflection;
    }

    /**
     * @param array-key $key
     * @return Collection<int, mixed>
     */
    public function pluck(int|string $key): Collection
    {
        return $this->newCollection(Arr::pluck($this->items, $key));
    }

    /**
     * @return Collection<int, TKey>
     */
    public function primaryKeys(): Collection
    {
        return $this->pluck($this->reflection->primaryKey);
    }

    /**
     * @return static<array-key, TModel>
     */
    public function keyByPrimaryKey(): static /** @phpstan-ignore-line */
    {
        return $this->keyBy($this->reflection->primaryKey);
    }

    /**
     * @return int
     */
    public function deleteAll(): int
    {
        return $this->countBy(static fn(?Model $model) => $model !== null && $model->delete());
    }
}
