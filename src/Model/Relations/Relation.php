<?php

namespace Kirameki\Model\Relations;

use Closure;
use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Model\QueryBuilder;
use Kirameki\Model\Reflection;
use Kirameki\Model\ModelManager;
use Kirameki\Support\Collection;

abstract class Relation
{
    /**
     * @var ModelManager
     */
    protected ModelManager $manager;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var Reflection
     */
    protected Reflection $srcReflection;

    /**
     * @var Reflection|null
     */
    protected ?Reflection $destReflection;

    /**
     * @var string
     */
    protected string $destClass;

    /**
     * @var ?string
     */
    protected ?string $srcKey;

    /**
     * @var string|null
     */
    protected ?string $destKey;

    /**
     * @var string|null
     */
    protected ?string $inverse;

    /**
     * @var Closure[]
     */
    protected array $scopes;

    /**
     * @param ModelManager $manager
     * @param string $name
     * @param Reflection $srcReflection
     * @param string $destClass
     * @param string|null $srcKey
     * @param string|null $refKey
     * @param string|null $inverse
     */
    public function __construct(ModelManager $manager, string $name, Reflection $srcReflection, string $destClass, ?string $srcKey = null, ?string $refKey = null, ?string $inverse = null)
    {
        $this->manager = $manager;
        $this->name = $name;
        $this->srcReflection = $srcReflection;
        $this->srcKey = $srcKey;
        $this->destReflection = null;
        $this->destClass = $destClass;
        $this->destKey = $refKey;
        $this->inverse = $inverse;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Reflection
     */
    public function getSrcReflection(): Reflection
    {
        return $this->srcReflection;
    }

    /**
     * @return string
     */
    abstract public function getSrcKeyName(): string;

    /**
     * @param Model $model
     * @return mixed|null
     */
    public function getSrcKey(Model $model)
    {
        return $model->getProperty($this->getSrcKeyName());
    }

    /**
     * @param RelationCollection $models
     * @return Collection
     */
    public function getSrcKeys(RelationCollection $models): Collection
    {
        return $models->pluck($this->getSrcKeyName());
    }

    /**
     * @return Reflection
     */
    public function getDestReflection(): Reflection
    {
        return $this->destReflection ??= $this->manager->reflect($this->destClass);
    }

    /**
     * @return string
     */
    abstract public function getDestKeyName(): string;

    /**
     * @return string|null
     */
    public function getInverseName(): ?string
    {
        return $this->inverse;
    }

    /**
     * @param string ...$names
     * @return $this
     */
    public function scope(string ...$names): static
    {
        foreach ($names as $name) {
            $this->scopes[$name] = $this->getDestReflection()->scopes[$name];
        }
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function buildQuery(): QueryBuilder
    {
        $db = $this->manager->getDatabaseManager();
        $query = new QueryBuilder($db, $this->getDestReflection());

        foreach ($this->scopes as $scope) {
            $scope($query);
        }

        return $query;
    }

    /**
     * @param Model $target
     * @return Model|Model[]|RelationCollection
     */
    abstract public function loadOnModel(Model $target);

    /**
     * @param ModelCollection $targets
     * @return Model|Model[]|ModelCollection
     */
    abstract public function loadOnCollection(ModelCollection $targets);
}
