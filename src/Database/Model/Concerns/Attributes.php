<?php

namespace Kirameki\Database\Model\Concerns;

use Kirameki\Database\Model\Model;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * @mixin Model
 */
trait Attributes
{
    protected static string $castInt = 'int';
    protected static string $castFloat = 'float';
    protected static string $castBool = 'bool';
    protected static string $castString = 'string';
    protected static string $castTimestamp = 'datetime';

    protected array $casts = [];
    protected array $attributes = [];

    protected static string $dateFormat = Carbon::RFC3339_EXTENDED;

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function rawAttributeExists(string $name)
    {
        return $this->hasCast($name);
    }

    protected function getAttribute(string $name)
    {
        return $this->attributes[$name];
    }

    protected function setAttribute(string $name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function hasCast(string $name)
    {
        return isset($this->casts[$name]);
    }

    public function getCast(string $name)
    {
        return $this->casts[$name] ?? null;
    }

    public function getCasts()
    {
        return $this->casts;
    }

    protected function toCastFormat(string $cast, $raw)
    {
        if (is_null($cast)) return $raw;
        if ($cast === static::$castInt) return (int) $raw;
        if ($cast === static::$castFloat) return (float) $raw;
        if ($cast === static::$castBool) return (bool) $raw;
        if ($cast === static::$castString) return (string) $raw;
        if ($cast === static::$castTimestamp) {
            if (is_string($raw)) return Carbon::parse($raw);
            if (is_int($raw)) return Carbon::createFromTimestamp($raw);
            if (is_float($raw)) return Carbon::createFromTimestampMs($raw * 1000);
            if ($raw instanceof DateTimeInterface) return Carbon::instance($raw);
        }
        // TODO allow custom casting
        return $raw;
    }
}
