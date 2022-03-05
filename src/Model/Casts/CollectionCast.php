<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Collection;

class CollectionCast extends ArrayCast
{
    /**
     * @param Model $model
     * @param string $key
     * @param string $value
     * @return Collection<array-key, mixed>
     */
    public function get(Model $model, string $key, mixed $value): array
    {
        return new Collection(parent::get($model, $key, $value));
    }
}
