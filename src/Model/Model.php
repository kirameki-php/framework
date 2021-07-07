<?php declare(strict_types=1);

namespace Kirameki\Model;

use ArrayAccess;
use JsonSerializable;
use RuntimeException;

abstract class Model implements ArrayAccess, JsonSerializable
{
    use Concerns\ArrayAccess,
        Concerns\Compare,
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
        return static::$manager ??= app()->get(ModelManager::class);
    }

    /**
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        $database = static::getManager()->getDatabaseManager();
        $reflection = static::getReflection();
        return new QueryBuilder($database, $reflection);
    }

    /**
     * @param array $properties
     * @param bool $persisted
     */
    public function __construct(array $properties = [], bool $persisted = false)
    {
        static::getReflection();

        $this->persisted = $persisted;

        if ($persisted) {
            // persisted properties are set directly as raw uncasted value
            // and are not deserialized until `getProperty` is called
            $this->setPersistedProperties($properties);
        } else {
            $this->setProperties($properties);
            $this->setDefaultProperties($properties);
        }
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
        else {
            throw new RuntimeException('Tried to set unknown property or relation: '.$name);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * @param string $name
     */
    public function __unset(string $name)
    {
        $this->offsetUnset($name);
    }

    /**
     * @param array $attributes
     * @param bool $persisted
     * @return $this
     */
    public function newInstance(array $attributes = [], $persisted = false): static
    {
        return new static($attributes, $persisted);
    }
}
