<?php

namespace Exelion\Support\Concerns;

use BadMethodCallException;
use Closure;

trait Macroable
{
    /**
     * @var Closure[]
     */
    protected static array $macros = [];

    public static function macro(string $name, Closure $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function macroExists( string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public static function __callStatic($method, $parameters)
    {
        return static::callMacro(null, $method, $parameters);
    }

    public function __call(string $method, $parameters)
    {
        return static::callMacro($this, $method, $parameters);
    }

    protected static function callMacro($newThis, string $method, $parameters)
    {
        if (! static::macroExists($method)) {
            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
        }
        return static::$macros[$method]
            ->bindTo($newThis, static::class)
            ->call($newThis, ...$parameters);
    }
}
