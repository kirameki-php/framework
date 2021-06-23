<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Json;

class ArrayCast implements CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return array
     */
    public function get(Model $model, string $key, $value): array
    {
        return Json::decode($value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param $value
     * @return string
     */
    public function set(Model $model, string $key, $value): string
    {
        return Json::encode($value);
    }
}
