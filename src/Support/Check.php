<?php

namespace Kirameki\Support;

class Check
{
    /**
     * @param mixed $value
     * @return bool
     */
    public static function isTrue(mixed $value): bool
    {
        Assert::bool($value);
        return $value === true;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isFalse(mixed $value): bool
    {
        Assert::bool($value);
        return $value === false;
    }
}
