<?php declare(strict_types=1);

namespace Kirameki\Model;

use Closure;
use Kirameki\Model\Relations\Relation;

class Reflection
{
    /**
     * @var string
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
     * @var string
     */
    public string $primaryKey;

    /**
     * @var Property[]
     */
    public array $properties;

    /**
     * @var Relation[]
     */
    public array $relations;

    /**
     * @var Closure[]
     */
    public array $scopes;

    /**
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->properties = [];
        $this->relations = [];
    }

    /**
     * @param array $properties
     * @param bool $persisted
     * @return Model
     */
    public function makeModel(array $properties = [], bool $persisted = false): Model
    {
        return new $this->class($properties, $persisted);
    }
}
