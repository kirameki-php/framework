<?php

namespace Kirameki\Database\Schema\Support;

/**
 * Just a dummy class for representing current timestamp
 */
class CurrentTimestamp
{
    protected static CurrentTimestamp $instance;

    public static function instance()
    {
        return static::$instance ??= new static;
    }
}