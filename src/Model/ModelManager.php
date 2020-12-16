<?php

namespace Kirameki\Model;

use Closure;
use Kirameki\Database\DatabaseManager;
use Kirameki\Model\Casts\CastInterface;

class ModelManager
{
    /**
     * @var DatabaseManager
     */
    protected DatabaseManager $databaseManager;

    /**
     * @var Reflection[]
     */
    protected array $reflections;

    /**
     * @var CastInterface[]
     */
    protected array $casts = [];

    /**
     * @var Closure[]
     */
    protected array $deferredCasts = [];

    /**
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * @return DatabaseManager
     */
    public function getDatabaseManager(): DatabaseManager
    {
        return $this->databaseManager;
    }

    /**
     * @param string $class
     * @return Reflection
     */
    public function reflect(string $class): Reflection
    {
        return $this->reflections[$class] ??= call_user_func("$class::getReflection");
    }

    /**
     * @param string $name
     * @return CastInterface
     */
    public function getCast(string $name): CastInterface
    {
        return $this->casts[$name] ??= call_user_func($this->deferredCasts[$name]);
    }

    /**
     * @param string $name
     * @param Closure $deferred
     * @return $this
     */
    public function setCast(string $name, Closure $deferred): static
    {
        $this->deferredCasts[$name] = $deferred;
        return $this;
    }
}
