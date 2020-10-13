<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Database\Connection;
use Kirameki\Model\Reflection;
use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Reflect
{
    /**
     * @var ?Reflection
     */
    protected static ?Reflection $reflection;

    /**
     * @return Reflection
     */
    protected static function getReflection(): Reflection
    {
        // Creating a new instance will call $this->reflectOnce()
        // which will resolve the reflection!
        if (static::$reflection === null) {
            new static();
        }
        return static::$reflection;
    }

    /**
     * @param Reflection $reflection
     */
    abstract public function define(Reflection $reflection): void;

    /**
     * @return void
     */
    public function resolveReflection(): void
    {
        if (static::$reflection === null) {
            $reflection = new Reflection(static::getManager(), get_class($this));
            $this->define($reflection);
            static::$reflection = $reflection;
        }
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        $db = static::getManager()->getDatabaseManager();
        $refletion = static::getReflection();
        return $db->using($refletion->connection);
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return static::getReflection()->table;
    }

    /**
     * @return string
     */
    public function getPrimaryKeyName(): string
    {
        return static::getReflection()->primaryKey;
    }

    /**
     * @return string|int|null
     */
    public function getPrimaryKey()
    {
        return $this->getProperty($this->getPrimaryKeyName());
    }
}
