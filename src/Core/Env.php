<?php

namespace Kirameki\Core;

class Env
{
    public static function get(string $key)
    {
        $value = strtolower($_ENV[$key] ?? $_SERVER[$key]);
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        return $value;
    }
}
