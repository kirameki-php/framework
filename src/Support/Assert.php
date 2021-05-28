<?php

namespace Kirameki\Support;

use Kirameki\Exception\InvalidKeyException;
use Kirameki\Exception\InvalidValueException;

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
}
