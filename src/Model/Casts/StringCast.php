<?php

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class StringCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function get(Model $model, string $key, $value)
    {
        return (string) $value;
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function set(Model $model, string $key, $value)
    {
        return (string) $value;
    }
}
