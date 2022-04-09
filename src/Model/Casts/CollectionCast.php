<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Collection;
use Kirameki\Support\Json;
use Traversable;

class CollectionCast implements Cast
{
    /**
     * @param Model $model
     * @param string $key
     * @param string $value
     * @return Collection<array-key, mixed>
     */
    public function get(Model $model, string $key, mixed $value): Collection
    {
        if (is_string($value)) {
            $value = Json::decode($value);
        }

        if ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        }

        return new Collection($value);
    }
}
