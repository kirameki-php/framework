<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Carbon\Carbon;
use Kirameki\Model\Model;

class DateTimeCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return Carbon
     */
    public function get(Model $model, string $key, $value): Carbon
    {
        return new Carbon($value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function set(Model $model, string $key, $value): mixed
    {
        // don't convert here, let the formatter take care of it
        return $value;
    }
}
