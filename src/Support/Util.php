<?php

namespace Kirameki\Support;

class Util
{
    public static function valueAsString($value)
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return (string) $value;
    }
}
