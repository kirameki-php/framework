<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;

interface CastInterface
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $value): mixed;
}
