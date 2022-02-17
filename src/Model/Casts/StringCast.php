<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

class StringCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @return string
     */
    public function get(Model $model, string $key, mixed $value): string
    {
        return (string) $value;
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @return string
     */
    public function set(Model $model, string $key, mixed $value): string
    {
        return (string) $value;
    }
}
