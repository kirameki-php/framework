<?php

namespace Kirameki\Model\Concerns;

use Kirameki\Model\Model;

/**
 * @mixin Model
 */
trait Events
{
    protected static Dispatcher $dispatcher;

    protected static bool $booted = false;

    public static function clearBooted()
    {
        static::$booted = false;
    }

    protected static function registerEvent(string $name, callable $callback)
    {
        static::$dispatcher->listen(static::makeEventName($name), $callback);
    }

    protected static function makeEventName(string $name)
    {
        return 'reduct:'.static::class.':'.$name;
    }

    public static function booting(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function booted(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function initialized(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function retrieved(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function creating(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function created(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function updating(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function updated(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function saving(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function saved(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function delete(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    public static function deleting(callable $callable)
    {
        static::registerEvent(__FUNCTION__, $callable);
    }

    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->triggerEvent('booting');

            static::boot();

            $this->triggerEvent('booted');
        }
    }

    protected function triggerEvent(string $name)
    {
        static::$dispatcher->until(static::makeEventName($name), $this);
    }
}
