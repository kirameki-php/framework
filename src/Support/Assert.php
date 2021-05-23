<?php

namespace Kirameki\Support;

use Kirameki\Exception\InvalidValueException;

class Assert
{
    /**
     * @param mixed $value
     */
    public static function bool(mixed $value): void
    {
        if (!is_bool($value)) {
            throw new InvalidValueException('bool', $value);
        }
    }

    /**
     * @param mixed $value
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
     */
    public static function greaterThan(int $expected, mixed $value): void
    {
        if (is_int($value) && $value > 1) {
            return;
        }
        throw new InvalidValueException('greater than '.$expected, $value);
    }
}
