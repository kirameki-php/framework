<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Closure;
use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Model\QueryBuilder;
use Kirameki\Model\Reflection;
use Kirameki\Model\ModelManager;
use Kirameki\Support\Collection;

/**
 * @template TSrc of Model
 * @template TDest of Model
 */
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
     * @var Reflection<TSrc>
     */
    protected Reflection $srcReflection;

    /**
     * @var Reflection<TDest>|null
     */
    protected ?Reflection $destReflection;

    /**
     * @var class-string<TDest>
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
     * @param Reflection<TSrc> $srcReflection
     * @param class-string<TDest> $destClass
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
     * @return Reflection<TSrc>
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
     * @param TSrc $model
     * @return scalar
     */
    public function getSrcKey(Model $model): mixed
    {
        return $model->getProperty($this->getSrcKeyName()); /** @phpstan-ignore-line */
    }

    /**
     * @return Reflection<TDest>
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
     * @return QueryBuilder<TDest>
     */
    protected function buildQuery(): QueryBuilder
    {
        $db = $this->manager->getDatabaseManager();
        $query = new QueryBuilder($db, $this->getDestReflection());

        foreach ($this->scopes as $scope) {
            $scope($query);
        }

        return $query;
    }

    /**
     * @param iterable<array-key> $srcKeys
     * @return ModelCollection<int, TDest>
     */
    protected function getDestModels(iterable $srcKeys): ModelCollection
    {
        return $this->buildQuery()
            ->where($this->getDestKeyName(), $srcKeys)
            ->all();
    }

    /**
     * @param iterable<int, TSrc> $srcModels
     * @return ModelCollection<int, TDest>
     */
    public function load(iterable $srcModels): ModelCollection
    {
        $mappedSrcModels = (new Collection($srcModels))
            ->keyBy($this->getSrcKeyName())
            ->compact();

        $destModels = $this->getDestModels($mappedSrcModels->keys());

        $destModelsGroupedByKey = $destModels->groupBy($this->getDestKeyName());

        foreach ($destModelsGroupedByKey as $key => $groupedDestModels) {
            $this->setDestToSrc($mappedSrcModels[$key], $groupedDestModels);
        }

        return $destModels;
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDest> $destModels
     * @return void
     */
    abstract protected function setDestToSrc(Model $srcModel, ModelCollection $destModels): void;
}
