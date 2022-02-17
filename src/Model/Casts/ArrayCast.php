<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Json;

class ArrayCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param string $value
     * @return array<array-key, mixed>
     */
    public function get(Model $model, string $key, mixed $value): array
    {
        return (array) Json::decode($value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @return string
     */
    public function set(Model $model, string $key, mixed $value): string
    {
        return Json::encode($value);
    }
}
