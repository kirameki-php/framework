<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class IntCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return int
     */
    public function get(Model $model, string $key, $value): int
    {
        return (int) $value;
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return int
     */
    public function set(Model $model, string $key, $value): int
    {
        return (int) $value;
    }
}
