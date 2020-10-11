<?php

namespace Kirameki\Model;

use Kirameki\Model\Relations\BelongsTo;

class Reflection
{
    protected ModelManager $manager;

    public string $class;

    public string $connection;

    public string $table;

    public string $primaryKey;

    public array $properties;

    public array $relations;

    /**
     * @param ModelManager $manager
     */
    public function __construct(ModelManager $manager, string $class)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->properties = [];
        $this->relations = [];
    }

    /**
     * @param string $name
     * @param string $cast
     * @param mixed|null $default
     * @return $this
     */
    public function property(string $name, string $cast, $default = null)
    {
        $this->properties[$name] = new Property($name, $this->manager->getCast($cast), $default);
        return $this;
    }

    /**
     * @param string $name
     * @param string|null $class
     * @param string|null $foreignKey
     * @param string|null $referenceKey
     * @param string|null $inverseOf
     * @return $this
     */
    public function belongsTo(string $name, ?string $class = null, ?string $foreignKey = null, ?string $referenceKey = null, ?string $inverseOf = null)
    {
        $this->relations[$name]= new BelongsTo($this->manager, $name, $this, $class, $foreignKey, $referenceKey, $inverseOf);
        return $this;
    }
}
