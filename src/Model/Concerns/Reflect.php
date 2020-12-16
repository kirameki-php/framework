<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Database\Connection;
use Kirameki\Model\Casts\CastInterface;
use Kirameki\Model\Reflection;
use Kirameki\Model\Model;
use Kirameki\Model\ReflectionBuilder;

/**
 * @mixin Model
 */
trait Reflect
{
    /**
     * @var Reflection|null
     */
    protected static ?Reflection $reflection = null;

    /**
     * @internal only used for test
     * @param Reflection $reflection
     */
    public static function setTestReflection(Reflection $reflection): void
    {
        static::$reflection = $reflection;
    }

    /**
     * @return Reflection
     */
    protected static function getReflection(): Reflection
    {
        if (static::$reflection === null) {
            $modelClass = static::class;
            $reflection = new Reflection($modelClass);

            if (method_exists($modelClass, 'define')) {
                $builder = new ReflectionBuilder(static::getManager(), $reflection);
                call_user_func($modelClass.'::define', $builder);
                $builder->applyDefaultsIfOmitted();
            } else {
                // TODO: auto resolve from table info
            }

            static::$reflection = $reflection;
        }
        return static::$reflection;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        $db = static::getManager()->getDatabaseManager();
        $reflection = static::getReflection();
        return $db->using($reflection->connection);
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return static::getReflection()->table;
    }

    /**
     * @param string $name
     * @return CastInterface
     */
    public function getCast(string $name): CastInterface
    {
        return static::getReflection()->properties[$name]->cast;
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
    public function getPrimaryKey(): string|int|null
    {
        return $this->getProperty($this->getPrimaryKeyName());
    }
}
