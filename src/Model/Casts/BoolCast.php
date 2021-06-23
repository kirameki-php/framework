<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class BoolCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return bool
     */
    public function get(Model $model, string $key, $value): bool
    {
        return (bool) $value;
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return bool
     */
    public function set(Model $model, string $key, $value): bool
    {
        return (bool) $value;
    }
}
