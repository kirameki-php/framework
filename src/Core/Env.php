<?php

namespace Kirameki\Core;

class Env
{
    /**
     * @param string $key
     * @return bool|string|null
     */
    public static function get(string $key): bool|string|null
    {
        $value = strtolower($_ENV[$key] ?? $_SERVER[$key]);
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        return $value;
    }
}
