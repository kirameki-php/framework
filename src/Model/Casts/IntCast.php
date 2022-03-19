<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class IntCast implements Cast
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public function get(Model $model, string $key, mixed $value): int
    {
        return (int) $value;
    }
}
