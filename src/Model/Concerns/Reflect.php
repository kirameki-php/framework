<?php

namespace Kirameki\Model\Concerns;

use Carbon\Carbon;
use Kirameki\Model\ModelManager;
use Kirameki\Model\Reflection;
use Kirameki\Model\Model;
use Kirameki\Support\Json;

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
     * @param Reflection $reflection
     */
    abstract public function define(Reflection $reflection): void;

    /**
     * @return Reflection
     */
    protected function getReflection(): Reflection
    {
        return static::$reflection;
    }

    /**
     * @return void
     */
    public function reflectOnce(): void
    {
        if (static::$reflection === null) {
            $reflection = new Reflection(static::getManager(), get_class($this));
            $this->define($reflection);
            static::$reflection = $reflection;
        }
    }

    /**
     * @return string
     */
    public function getPrimaryKeyName(): string
    {
        return $this->getReflection()->primaryKey;
    }

    /**
     * @return string|int|null
     */
    public function getPrimaryKey()
    {
        return $this->getProperty($this->getPrimaryKeyName());
    }
}
