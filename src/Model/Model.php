<?php

namespace Kirameki\Model;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;

abstract class Model implements ArrayAccess, JsonSerializable
{
    use Concerns\ArrayAccess,
        Concerns\CacheResults,
        Concerns\Reflect,
        Concerns\JsonSerialize,
        Concerns\Persistence,
        Concerns\Properties,
        Concerns\Relations;

    /**
     * @var ModelManager
     */
    protected static ModelManager $manager;

    /**
     * @param ModelManager $manager
     */
    protected static function setManager(ModelManager $manager): void
    {
        static::$manager = $manager;
    }

    /**
     * @return ModelManager $manager
     */
    protected static function getManager(): ModelManager
    {
        return static::$manager;
    }

    /**
     * @param array $properties
     * @param bool $persisted
     */
    public function __construct(array $properties = [], bool $persisted = false)
    {
        $this->reflectOnce();
        $this->persisted = $persisted;
        $this->fill($properties);
        if ($this->isNewRecord()) {
            $this->fillDefaults();
        }
    }

    /**
     * @param array $attributes
     * @param bool $persisted
     * @return $this
     */
    public function newInstance(array $attributes = [], $persisted = false)
    {
        return new static($attributes, $persisted);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if ($this->isProperty($name)) {
            return $this->getProperty($name);
        }
        if ($this->isRelation($name)) {
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
        if ($this->isRelation($name)) {
            $this->setRelation($name, $value);
        }
        elseif ($this->isProperty($name)) {
            $this->setProperty($name, $value);
        }
        throw new RuntimeException('Tried to set unknown property or relation: '.$name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return $this->offsetExists($name);
    }
}
