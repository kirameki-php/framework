<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Time;
use Stringable;

class DateTimeCast implements Cast
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
}
