<?php declare(strict_types=1);

namespace Kirameki\Support\Concerns;

use BadMethodCallException;
use Closure;
use function sprintf;

trait Macroable
{
    /**
     * @var Closure[]
     */
    protected static array $macros = [];

    /**
     * @param string $name
     * @param Closure $macro
     * @return void
     */
    public static function macro(string $name, Closure $macro): void
    {
        static::$macros[$name] = $macro;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function macroExists(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        return static::callMacro(null, $method, $parameters);
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return static::callMacro($this, $method, $parameters);
    }

    /**
     * @param Macroable $newThis
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    protected static function callMacro(self $newThis, string $method, array $parameters): mixed
    {
        if (! static::macroExists($method)) {
            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
        }
        return static::$macros[$method]?->call($newThis, ...$parameters);
    }
}
