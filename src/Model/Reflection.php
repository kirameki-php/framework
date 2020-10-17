<?php

namespace Kirameki\Model;

use Closure;
use Kirameki\Model\Relations\Relation;

class Reflection
{
    public string $class;

    public string $connection;

    public string $table;

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
