<?php declare(strict_types=1);

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
     * @var array<class-string, Reflection<Model>>
     */
    protected array $reflections;

    /**
     * @var array<non-empty-string, CastInterface>
     */
    protected array $casts = [];

    /**
     * @var array<string, callable(): CastInterface>
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
     * @template T of Model
     * @param class-string<T> $class
     * @return Reflection<T>
     */
    public function reflect(string $class): Reflection
    {
        return $this->reflections[$class] ??= call_user_func("$class::getReflection"); /** @phpstan-ignore-line */
    }

    /**
     * @param string $name
     * @return CastInterface
     */
    public function getCast(string $name): CastInterface
    {
        return $this->casts[$name] ??= call_user_func($this->deferredCasts[$name]); /** @phpstan-ignore-line */
    }

    /**
     * @param string $name
     * @param callable(): CastInterface $deferred
     * @return $this
     */
    public function setCast(string $name, callable $deferred): static
    {
        $this->deferredCasts[$name] = $deferred;
        return $this;
    }
}
