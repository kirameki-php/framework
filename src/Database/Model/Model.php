<?php

namespace Kirameki\Database\Model;

use ArrayAccess;
use JsonSerializable;
use Kirameki\Support\Json;

class Model implements ArrayAccess, JsonSerializable
{
    use Concerns\Attributes,
        Concerns\Dirty,
        Concerns\Events,
        Concerns\Querying,
        Concerns\Relations;

    public static function basename(): string
    {
        return class_basename(static::class);
    }

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
        $this->triggerEvent($exists ? 'retrieved' : 'initialized');
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
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
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
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

    public function toArray(): array
    {
        $array = [];
        foreach (array_keys($this->getAttributes()) as $name) {
            $array[$name] = $this->getAttribute($name);
        }
        return $array;
    }

    public function toJson($options = 0): string
    {
        return Json::encode($this, $options);
    }
}
