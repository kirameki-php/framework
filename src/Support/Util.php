<?php

namespace Kirameki\Support;

use DateTimeInterface;

class Util
{
    /**
     * @param mixed $value
     * @return string
     */
    public static function toString(mixed $value): string
    {
        if (is_null($value)) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_array($value)) return Json::encode($value);
        if (is_resource($value)) return get_resource_type($value);
        if ($value instanceof DateTimeInterface) return $value->format(DATE_RFC3339_EXTENDED);
        if (is_object($value)) return get_class($value).':'.spl_object_hash($value);
        return (string) $value;
    }

    /**
     * @param mixed $var
     * @return string
     */
    public static function typeOf(mixed $var): string
    {
        if (is_null($var)) return "null";
        if (is_bool($var)) return "bool";
        if (is_int($var)) return "int";
        if (is_float($var)) return "float";
        if (is_string($var)) return "string";
        if (is_array($var)) return "array";
        if ($var instanceof DateTimeInterface) return 'datetime';
        if (is_object($var)) return "object";
        if (is_resource($var)) return "resource";
        return "unknown type";
    }
}
