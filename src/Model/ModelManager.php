<?php declare(strict_types=1);

namespace Kirameki\Model;

use Closure;
use Kirameki\Database\DatabaseManager;
use Kirameki\Model\Casts\Cast;
use RuntimeException;

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
     * @var array<string, Cast>
     */
    protected array $casts = [];

    /**
     * @var array<string, callable(string): Cast>
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
        return $this->reflections[$class] ??= $class::getReflection(); /** @phpstan-ignore-line */
    }

    /**
     * @param string $name
     * @return Cast
     */
    public function getCast(string $name): Cast
    {
        if (isset($this->casts[$name])) {
            return $this->casts[$name];
        }

        if (isset($this->deferredCasts[$name])) {
            $this->casts[$name] = $this->deferredCasts[$name]($name);
            return $this->casts[$name];
        }

        if (enum_exists($name)) {
            return $this->casts[$name] = $this->deferredCasts['{enum}']($name);
        }

        throw new RuntimeException('Unknown cast:' .$name);
    }

    /**
     * @param string $name
     * @param Closure(string): Cast $deferred
     * @return $this
     */
    public function setCast(string $name, Closure $deferred): static
    {
        $this->deferredCasts[$name] = $deferred;
        return $this;
    }
}
