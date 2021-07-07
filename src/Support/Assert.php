<?php declare(strict_types=1);

namespace Kirameki\Support;

use Kirameki\Exception\InvalidKeyException;
use Kirameki\Exception\InvalidValueException;
use RuntimeException;
use function is_bool;
use function is_int;
use function is_string;

class Assert
{
    /**
     * @param mixed $value
     * @return void
     */
    public static function bool(mixed $value): void
    {
        if (!is_bool($value)) {
            throw new InvalidValueException('bool', $value);
        }
    }

    /**
     * @param mixed $value
     * @return void
     */
    public static function int(mixed $value): void
    {
        if (!is_int($value)) {
            throw new InvalidValueException('int', $value);
        }
    }

    /**
     * @param mixed $value
     * @return void
     */
    public static function object(mixed $value): void
    {
        if (!is_object($value)) {
            throw new InvalidValueException('object', $value);
        }
    }

    /**
     * @param mixed $value
     * @return void
     */
    public static function positiveInt(mixed $value): void
    {
        if (is_int($value) && $value > 0) {
            return;
        }
        throw new InvalidValueException('positive int', $value);
    }

    /**
     * @param int $expected
     * @param mixed $value
     * @return void
     */
    public static function greaterThan(int $expected, mixed $value): void
    {
        if (is_int($value) && $value > 1) {
            return;
        }
        throw new InvalidValueException('greater than '.$expected, $value);
    }

    /**
     * @param int|string $key
     * @return void
     */
    public static function validKey(mixed $key): void
    {
        if (is_string($key) || is_int($key)) {
            return;
        }
        throw new InvalidKeyException($key);
    }

    /**
     * @param string|object $class
     * @return void
     */
    public static function isClass(string|object $class): void
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if(!class_exists($class)) {
            throw new RuntimeException("Class: $class does not exist.");
        }
    }

    /**
     * @param string|object $is
     * @param string $of
     * @return void
     */
    public static function isClassOf(string|object $is, string $of): void
    {
        if (is_object($is)) {
            $is = get_class($is);
        }

        if(!is_a($is, $of, true)) {
            throw new RuntimeException("$is must be class or subclass of $of.");
        }
    }
}
