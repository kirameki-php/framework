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
 * @template TDst of Model
 */
abstract class Relation
{
    /**
     * @var ModelManager
     */
    protected ModelManager $manager;

    /**
     * @var non-empty-string
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
     * @var non-empty-string|null
     */
    protected ?string $inverse;

    /**
     * @var Closure[]
     */
    protected array $scopes;

    /**
     * @param ModelManager $manager
     * @param non-empty-string $name
     * @param Reflection<TSrc> $srcReflection
     * @param class-string<TDst> $dstClass
     * @param non-empty-array<non-empty-string, non-empty-string> $keyPairs [$srcKeyName => $dstKeyName, ...]
     * @param non-empty-string|null $inverse
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
     * @return non-empty-string
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
     * @return non-empty-array<non-empty-string, non-empty-string>
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
        return $this->getKeyPairs()->values(); /** @phpstan-ignore-line */
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
     * @param string ...$names
     * @return $this
     */
    public function scope(string ...$names): static
    {
        foreach ($names as $name) {
            $this->scopes[$name] = $this->getDstReflection()->scopes[$name];
        }
        return $this;
    }

    /**
     * @return QueryBuilder<TDst>
     */
    protected function buildQuery(): QueryBuilder
    {
        $db = $this->manager->getDatabaseManager();
        $query = new QueryBuilder($db, $this->getDstReflection());

        foreach ($this->scopes as $scope) {
            $scope($query);
        }

        return $query;
    }

    /**
     * @param ModelCollection<array-key, TSrc> $srcModels
     * @return ModelCollection<int, TDst>
     */
    protected function getDstModels(iterable $srcModels): ModelCollection
    {
        $query = $this->buildQuery();

        foreach ($this->keyPairs as $srcName => $dstName) {
            $srcKeys = $srcModels->pluck($srcName)->compact();
            $query->where($dstName, $srcKeys);
        }

        return $query->all();
    }

    /**
     * @param iterable<int, TSrc> $srcModels
     * @return ModelCollection<int, TDst>
     */
    public function load(iterable $srcModels): ModelCollection
    {
        $keyedSrcModels = (new ModelCollection($this->srcReflection, $srcModels))
            ->keyBy(fn(Model $model) => $this->getSrcKeys($model)->join('|'));

        $dstModels = $this->getDstModels($keyedSrcModels);

        $dstModelGroups = $dstModels->groupBy(fn(Model $model) => $this->getDstKeys($model)->join('|'));

        foreach ($dstModelGroups as $key => $groupedDstModels) {
            $this->setDstToSrc($keyedSrcModels[$key], $groupedDstModels);
        }

        return $dstModels;
    }

    /**
     * @param TSrc $srcModel
     * @param ModelCollection<int, TDst> $dstModels
     * @return void
     */
    abstract protected function setDstToSrc(Model $srcModel, ModelCollection $dstModels): void;
}
