<?php

namespace Kirameki\Model;

use Kirameki\Model\Associations\BelongsTo;

class Definition
{
    protected CastRegistrar $casts;

    public Model $model;

    public string $connection;

    public string $table;

    public string $primaryKey;

    public array $properties;

    public array $associations;

    /**
     * @param CastRegistrar $casts
     * @param Model $model
     */
    public function __construct(CastRegistrar $casts, Model $model)
    {
        $this->casts = $casts;
        $this->model = $model;
        $this->properties = [];
        $this->associations = [];
    }

    /**
     * @param string $name
     * @param string $cast
     * @param mixed|null $default
     * @return $this
     */
    public function property(string $name, string $cast, $default = null)
    {
        $this->properties[$name] = new Property($name, $this->casts->get($cast), $default);
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
        $this->associations[$name]= new BelongsTo($name, $this->model, new $class, $foreignKey, $referenceKey, $inverseOf);
        return $this;
    }
}
