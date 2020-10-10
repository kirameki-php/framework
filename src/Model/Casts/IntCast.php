<?php

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class IntCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function get(Model $model, string $key, $value)
    {
        return (int) $value;
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function set(Model $model, string $key, $value)
    {
        return (int) $value;
    }
}
