<?php declare(strict_types=1);

namespace Kirameki\Model;

use Closure;
use Kirameki\Model\Relations\BelongsTo;
use Kirameki\Model\Relations\HasMany;
use Kirameki\Model\Relations\Relation;

/**
 * @template TModel of Model
 */
class ReflectionBuilder
{
    /**
     * @var ModelManager
     */
    protected ModelManager $manager;

    /**
     * @var Reflection<TModel>
     */
    protected Reflection $reflection;

    /**
     * @param ModelManager $manager
     * @param Reflection<TModel> $reflection
     */
    public function __construct(ModelManager $manager, Reflection $reflection)
    {
        $this->manager = $manager;
        $this->reflection = $reflection;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function connection(string $name): static
    {
        $this->reflection->connectionName = $name;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function table(string $name): static
    {
        $this->reflection->tableName = $name;
        return $this;
    }

    /**
     * @param string $name
     * @param string $cast
     * @param mixed|null $default
     */
    public function property(string $name, string $cast, mixed $default = null): void
    {
        $this->reflection->properties[$name] = new Property($name, $this->manager->getCast($cast), $default);
    }

    /**
     * @template TDst of Model
     * @param string $name
     * @param class-string<TDst> $class
     * @param array<string, string> $keyPairs
     * @param string|null $inverse
     */
    public function belongsTo(string $name, string $class, array $keyPairs = null, ?string $inverse = null): void
    {
        $this->addRelation(new BelongsTo($this->manager, $name, $this->reflection, $class, $keyPairs, $inverse));
    }

    /**
     * @template TDst of Model
     * @param string $name
     * @param class-string<TDst> $class
     * @param array<string, string> $keyPairs
     * @param string|null $inverse
     */
    public function hasMany(string $name, string $class, array $keyPairs = null, ?string $inverse = null): void
    {
        $this->addRelation(new HasMany($this->manager, $name, $this->reflection, $class, $keyPairs, $inverse));
    }

    /**
     * @param Relation<Model, Model> $relation
     * @return void
     */
    protected function addRelation(Relation $relation): void
    {
        $this->reflection->relations[$relation->getName()] = $relation;
    }

    /**
     * @param string $name
     * @param Closure(QueryBuilder<TModel>):void $callback
     */
    public function scope(string $name, Closure $callback): void
    {
        $this->reflection->scopes[$name] = $callback;
    }

    /**
     * @internal
     * @return void
     */
    public function applyDefaultsIfOmitted(): void
    {
        $this->reflection->connectionName ??= config()->getString('database.default');
        $this->reflection->tableName ??= class_basename($this->reflection->class);
    }
}
