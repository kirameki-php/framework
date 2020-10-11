<?php

namespace Kirameki\Model;

use ArrayAccess;
use JsonSerializable;
use Kirameki\Support\Json;

abstract class Model implements ArrayAccess, JsonSerializable
{
    use Concerns\ArrayAccess,
        Concerns\JsonSerialize,
        Concerns\Reflection;

    protected static function boot()
    {

    }

    public static function query()
    {
        return (new static())->newQuery();
    }

    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->exists = $exists;
        $this->bootIfNotBooted();
        $this->fill($attributes);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if ($this->rawAttributeExists($name)) {
            return $this->getAttribute($name);
        }
        if ($this->relationExists($name)) {
            return $this->getRelation($name);
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value): void
    {
        if ($this->relationExists($name)) {
            $this->setRelation($name, $value);
            return;
        }
        $this->setAttribute($name, $value);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return $this->offsetExists($name);
    }

    /**
     * @param array $attributes
     * @param false $exists
     * @return $this
     */
    public function newInstance(array $attributes = [], $exists = false)
    {
        return new static($attributes, $exists);
    }

    /**
     * @param array $attributes
     */
    public function fill(array $attributes = [])
    {
        foreach ($attributes as $name => $attribute) {
            $this->setAttribute($name, $attribute);
        }
    }
}
