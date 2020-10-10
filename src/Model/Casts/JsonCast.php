<?php

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Json;

class JsonCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function get(Model $model, string $key, $value)
    {
        return Json::decode($value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function set(Model $model, string $key, $value)
    {
        return Json::encode($value);
    }
}
