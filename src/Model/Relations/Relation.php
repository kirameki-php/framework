<?php declare(strict_types=1);

namespace Kirameki\Model\Relations;

use Kirameki\Model\Model;
use Kirameki\Model\ModelCollection;
use Kirameki\Model\QueryBuilder;
use Kirameki\Model\Reflection;
use Kirameki\Model\ModelManager;
use Kirameki\Support\Collection;

/**
 * @template TSrc of Model
 * @template TDst of Model
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
     * @var Reflection<TDst>|null
     */
    protected ?Reflection $dstReflection;

    /**
     * @var class-string<TDst>
     */
    protected string $dstClass;

    /**
     * @var Collection<string, string>
     */
    protected Collection $keyPairs;

    /**
     * @var string|null
     */
    protected ?string $inverse;

    /**
     * @var array<int, callable>
     */
    protected array $scopes;

    /**
     * @param ModelManager $manager
     * @param string $name
     * @param Reflection<TSrc> $srcReflection
     * @param class-string<TDst> $dstClass
     * @param array<string, string> $keyPairs should look like [$srcKeyName => $dstKeyName, ...]
     * @param string|null $inverse
     */
    public function __construct(ModelManager $manager, string $name, Reflection $srcReflection, string $dstClass, array $keyPairs = null, ?string $inverse = null)
    {
        $this->manager = $manager;
        $this->name = $name;
        $this->srcReflection = $srcReflection;
        $this->dstReflection = null;
        $this->dstClass = $dstClass;
        $this->keyPairs = new Collection($keyPairs ?: $this->guessKeyPairs());
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
     * @return Collection<string, string>
     */
    public function getKeyPairs(): Collection
    {
        return $this->keyPairs;
    }

    /**
     * @return array<string, string>
     */
    abstract protected function guessKeyPairs(): array;

    /**
     * @return Reflection<TSrc>
     */
    public function getSrcReflection(): Reflection
    {
        return $this->srcReflection;
    }

    /**
     * @return Collection<int, string>
     */
    public function getSrcKeyNames(): Collection
    {
        return $this->getKeyPairs()->keys();
    }

    /**
     * @param TSrc $srcModel
     * @return Collection<int, mixed>
     */
    protected function getSrcKeys(Model $srcModel): Collection
    {
        return $this->getSrcKeyNames()->map(static fn(string $name) => $srcModel->getProperty($name));
    }

    /**
     * @return Reflection<TDst>
     */
    public function getDstReflection(): Reflection
    {
        return $this->dstReflection ??= $this->manager->reflect($this->dstClass);
    }

    /**
     * @return Collection<int, string>
     */
    public function getDstKeyNames(): Collection
    {
        return $this->getKeyPairs()->values();
    }

    /**
     * @param Model $model
     * @return Collection<int, mixed>
     */
    protected function getDstKeys(Model $model): Collection
    {
        return $this->getDstKeyNames()->map(static fn(string $name) => $model->getProperty($name));
    }

    /**
     * @return string|null
     */
    public function getInverseName(): ?string
    {
        return $this->inverse;
    }

    /**
     * @param string|callable(QueryBuilder<TDst>, ModelCollection<int, TSrc>): QueryBuilder<TDst> $scope
     * @return $this
     */
    public function scope(string|callable $scope): static
    {
        $this->scopes[] = is_string($scope)
            ? $this->getDstReflection()->scopes[$scope]
            : $scope;

        return $this;
    }

    /**
     * @param iterable<int, TSrc> $srcModels
     * @return ModelCollection<int, TDst>
     */
    public function load(iterable $srcModels): ModelCollection
    {
        $srcModels = $this->srcModelsToCollection($srcModels);
        $dstModels = $this->getDstModels($srcModels);

        $keyedSrcModels = $srcModels->keyBy(fn(Model $model) => $this->getSrcKeys($model)->join('|'));
        $dstModelGroups = $dstModels->groupBy(fn(Model $model) => $this->getDstKeys($model)->join('|'));

        foreach ($dstModelGroups as $key => $groupedDstModels) {
            $this->setDstToSrc($keyedSrcModels[$key], $groupedDstModels);
        }

        return $dstModels;
    }

    /**
     * @param iterable<int, TSrc> $srcModels
     * @return ModelCollection<int, TSrc>
     */
    protected function srcModelsToCollection(iterable $srcModels): ModelCollection
    {
        if ($srcModels instanceof ModelCollection) {
            return $srcModels;
        }

        if ($srcModels instanceof Collection) {
            $srcModels = $srcModels->toArray();
        }

        return new ModelCollection($this->srcReflection, $srcModels);
    }

    /**
     * @param ModelCollection<int, TSrc> $srcModels
     * @return ModelCollection<int, TDst>
     */
    protected function getDstModels(ModelCollection $srcModels): ModelCollection
    {
        $query = $this->newQuery();
        $this->addConstraintsToQuery($query, $srcModels);
        $this->addScopesToQuery($query, $srcModels);
        return $query->all();
    }

    /**
     * @return QueryBuilder<TDst>
     */
    protected function newQuery(): QueryBuilder
    {
        return new QueryBuilder($this->manager->getDatabaseManager(), $this->getDstReflection());
    }

    /**
     * @param QueryBuilder<TDst> $query
     * @param ModelCollection<int, TSrc> $srcModels
     * @return void
     */
    protected function addConstraintsToQuery(QueryBuilder $query, ModelCollection $srcModels): void
    {
        foreach ($this->keyPairs as $srcName => $dstName) {
            $srcKeys = $srcModels->pluck($srcName)->compact();
            $query->where($dstName, $srcKeys);
        }
    }

    /**
     * @param QueryBuilder<TDst> $query
     * @param ModelCollection<int, TSrc> $srcModels
     * @return void
     */
    protected function addScopesToQuery(QueryBuilder $query, ModelCollection $srcModels): void
    {
        foreach ($this->scopes as $scope) {
            $scope($query, $srcModels);
        }
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDst> $dstModels
     * @return void
     */
    abstract protected function setDstToSrc(Model $srcModel, ModelCollection $dstModels): void;
}
