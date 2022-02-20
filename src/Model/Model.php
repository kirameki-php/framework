<?php declare(strict_types=1);

namespace Kirameki\Model;

use ArrayAccess;
use JsonSerializable;
use Kirameki\Model\Relations\RelationCollection;
use RuntimeException;

/**
 * @implements ArrayAccess<string, mixed>
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    use Concerns\ArrayAccess;
    use Concerns\Compare;
    use Concerns\Serialization;
    use Concerns\Persistence;
    use Concerns\Properties;
    use Concerns\Reflect;
    use Concerns\Relations;

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
     * @return ModelManager
     */
    protected static function getManager(): ModelManager
    {
        return static::$manager ??= app()->get(ModelManager::class);
    }

    /**
     * @return QueryBuilder<static>
     */
    public static function query(): QueryBuilder
    {
        $database = static::getManager()->getDatabaseManager();
        $reflection = static::getReflection();
        return new QueryBuilder($database, $reflection);
    }

    /**
     * @param array<string, mixed> $properties
     * @param bool $persisted
     */
    public function __construct(array $properties = [], bool $persisted = false)
    {
        static::getReflection();

        $this->_persisted = $persisted;

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
     * @return mixed
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
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        if ($this->isProperty($name)) {
            $this->setProperty($name, $value);
            return;
        }

        if ($this->isRelation($name)) {
            /** @var Model|RelationCollection<Model, Model> $value */
            $this->setRelation($name, $value);
            return;
        }

        throw new RuntimeException('Tried to set unknown property or relation: '.$name);
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
    public function __unset(string $name): void
    {
        $this->offsetUnset($name);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param bool $persisted
     * @return static
     */
    public function newInstance(array $attributes = [], $persisted = false): static
    {
        return new static($attributes, $persisted);
    }
}
