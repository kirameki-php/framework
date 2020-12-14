<?php

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class FloatCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return float
     */
    public function get(Model $model, string $key, $value): float
    {
        return (float) $value;
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return float
     */
    public function set(Model $model, string $key, $value): float
    {
        return (float) $value;
    }
}
