<?php

namespace Kirameki\Model\Casts;

use Carbon\Carbon;
use Kirameki\Model\Model;

class DateTimeCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function get(Model $model, string $key, $value)
    {
        return new Carbon($value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function set(Model $model, string $key, $value)
    {
        // don't convert here, let the formatter take care of it
        return $value;
    }
}
