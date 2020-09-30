<?php

namespace Kirameki\Support;

use RuntimeException;

class Assert
{
    /**
     * @param $value
     * @return bool
     */
    public static function isTrue($value): bool
    {
        return static::boolCheck($value, true);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function isFalse($value): bool
    {
        return static::boolCheck($value, false);
    }

    /**
     * @param $value
     * @param bool $expected
     * @return bool
     */
    protected static function boolCheck($value, bool $expected): bool
    {
        if (!is_bool($value)) {
            $result = Util::toString($value);
            $message = "Invalid return value: $result. Call must return a boolean value";
            throw new RuntimeException($message);
        }
        return $value === $expected;
    }
}
