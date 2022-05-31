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
    protected static array $_macros = [];

    /**
     * @param string $name
     * @param Closure $macro
     * @return void
     */
    public static function macro(string $name, Closure $macro): void
    {
        static::$_macros[$name] = $macro;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function macroExists(string $name): bool
    {
        return isset(static::$_macros[$name]);
    }

    /**
     * @param string $method
     * @param array<int|string, mixed> $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        return static::callMacro($method, $parameters);
    }

    /**
     * @param string $method
     * @param array<int|string, mixed> $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return static::callMacro($method, $parameters);
    }

    /**
     * @param string $method
     * @param array<int|string, mixed> $parameters
     * @return mixed
     */
    protected static function callMacro(string $method, array $parameters): mixed
    {
        if (isset(static::$_macros[$method])) {
            return static::$_macros[$method](...$parameters);
        }
        throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
    }
}
