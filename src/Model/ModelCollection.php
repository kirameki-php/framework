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
    public function getReflection(): Reflection
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
     * @param array<string, mixed> $properties
     * @return TModel
     */
    public function make(array $properties = []): Model
    {
        $model = $this->reflection->makeModel($properties);
        $this->append($model);
        return $model;
    }

    /**
     * @template UKey of array-key
     * @template UValue
     * @param iterable<UKey, UValue> $items
     * @return Collection<UKey, UValue>
     */
    protected function newCollection(iterable $items): Collection
    {
        return new Collection($items);
    }
}
