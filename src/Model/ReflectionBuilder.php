<?php

namespace Kirameki\Model;

use Closure;
use Kirameki\Model\Relations\BelongsTo;
use Kirameki\Model\Relations\HasMany;

class ReflectionBuilder
{
    protected ModelManager $manager;

    protected Reflection $reflection;

    /**
     * @param ModelManager $manager
     * @param Reflection $reflection
     */
    public function __construct(ModelManager $manager, Reflection $reflection)
    {
        $this->manager = $manager;
        $this->reflection = $reflection;
    }

    /**
     * @param string $connection
     * @return $this
     */
    public function connection(string $connection): static
    {
        $this->reflection->connection = $connection;
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table(string $table): static
    {
        $this->reflection->table = $table;
        return $this;
    }

    /**
     * @param string $name
     * @param string $cast
     * @param mixed|null $default
     */
    public function property(string $name, string $cast, $default = null)
    {
        $this->reflection->properties[$name] = new Property($name, $this->manager->getCast($cast), $default);
    }

    /**
     * @param string $name
     * @param string $class
     * @param string|null $foreignKey
     * @param string|null $referenceKey
     * @param string|null $inverseOf
     */
    public function belongsTo(string $name, string $class, ?string $foreignKey = null, ?string $referenceKey = null, ?string $inverseOf = null)
    {
        $this->reflection->relations[$name] = new BelongsTo($this->manager, $name, $this->reflection, $class, $foreignKey, $referenceKey, $inverseOf);
    }

    /**
     * @param string $name
     * @param string $class
     * @param string|null $foreignKey
     * @param string|null $referenceKey
     * @param string|null $inverseOf
     */
    public function hasMany(string $name, string $class, ?string $foreignKey = null, ?string $referenceKey = null, ?string $inverseOf = null)
    {
        $this->reflection->relations[$name] = new HasMany($this->manager, $name, $this->reflection, $class, $foreignKey, $referenceKey, $inverseOf);
    }

    /**
     * @param string $name
     * @param Closure<QueryBuilder> $callback
     */
    public function scope(string $name, Closure $callback)
    {
        $this->reflection->scopes[$name] = $callback;
    }

    /**
     * @internal
     * @return void
     */
    public function applyDefaultsIfOmitted(): void
    {
        $this->reflection->connection ??= config()->get('database.default');
        $this->reflection->table ??= class_basename($this->reflection->class);
    }
}
