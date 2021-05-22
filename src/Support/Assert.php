<?php

namespace Kirameki\Support;

use Kirameki\Exception\ReturnValueException;

class Assert
{
    /**
     * @param mixed $value
     */
    public static function bool(mixed $value): void
    {
        if (!is_bool($value)) {
            throw new ReturnValueException($value, "Call must return a bool value.");
        }
    }
}
