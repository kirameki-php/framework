<?php declare(strict_types=1);

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Model\Relations\RelationCollection;
use Kirameki\Support\Arr;

/**
 * @mixin Model
 */
trait Relations
{
    /**
     * @var array
     */
    protected array $relations;

    /**
     * @param string|string[] $relationNames
     * @return $this
     */
    public function preload(array $relationNames): static
    {
        $this->preloadRecursive($this, $relationNames);
        return $this;
    }

    /**
     * @template TModel of Model
     * @param TModel|RelationCollection<TModel> $target
     * @param array $names
     */
    protected function preloadRecursive(RelationCollection|Model $target, array $names): void
    {
        if (Arr::isList($names)) {
            foreach ($names as $name) {
                $this->loadRelation($target, $name);
            }
        }
        else {
            foreach ($names as $name => $inner) {
                $model = $this->loadRelation($target, $name);
                $this->preloadRecursive($model, Arr::wrap($inner));
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRelation(string $name): bool
    {
        return isset(static::getReflection()->relations[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRelationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    /**
     * @return array<Model|RelationCollection>
     */
    public function getRelations(): array
    {
        return $this->relations ??= [];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getRelation(string $name): mixed
    {
        if (!array_key_exists($name, $this->relations)) {
            $this->loadRelation([$this], $name);
        }
        return $this->relations[$name];
    }

    /**
     * @template TModel of Model
     * @param iterable<TModel> $srcModels
     * @param string $name
     * @return ModelCollection<TModel>
     */
    protected function loadRelation(iterable $srcModels, string $name): ModelCollection
    {
        return static::getReflection()->relations[$name]->load($srcModels);
    }

    /**
     * @template TModel of Model
     * @param string $name
     * @param TModel|iterable<int, TModel> $models
     * @return $this
     */
    public function setRelation(string $name, mixed $models): static
    {
        $this->relations[$name] = Arr::from($models);
        return $this;
    }
}
