<?php declare(strict_types=1);

namespace Kirameki\Model;

use Closure;
use Kirameki\Model\Relations\Relation;

/**
 * @template TModel of Model
 */
class Reflection
{
    /**
     * @var class-string<TModel>
     */
    public string $class;

    /**
     * @var string
     */
    public string $connection;

    /**
     * @var string
     */
    public string $table;

    /**
     * @var non-empty-string
     */
    public string $primaryKey;

    /**
     * @var Property[]
     */
    public array $properties;

    /**
     * @var array<non-empty-string, Relation<TModel, Model>>
     */
    public array $relations;

    /**
     * @var Closure[]
     */
    public array $scopes;

    /**
     * @param class-string<TModel> $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->properties = [];
        $this->relations = [];
    }

    /**
     * @param array<string, mixed> $properties
     * @param bool $persisted
     * @return TModel
     */
    public function makeModel(array $properties = [], bool $persisted = false): Model
    {
        return new $this->class($properties, $persisted);
    }
}
