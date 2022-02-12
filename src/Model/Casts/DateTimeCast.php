<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Time;
use Stringable;

class DateTimeCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param scalar|Stringable $value
     * @return Time
     */
    public function get(Model $model, string $key, mixed $value): Time
    {
        return new Time((string) $value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value): mixed
    {
        // don't convert here, let the formatter take care of it
        return $value;
    }
}
