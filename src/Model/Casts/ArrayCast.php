<?php declare(strict_types=1);

namespace Kirameki\Model\Casts;

use Kirameki\Model\Model;
use Kirameki\Support\Collection;
use Kirameki\Support\Json;
use Kirameki\Support\Str;
use RuntimeException;
use Traversable;

class ArrayCast implements Cast
{
    /**
     * @param Model $model
     * @param string $key
     * @param string $value
     * @return array<array-key, mixed>
     */
    public function get(Model $model, string $key, mixed $value): array
    {
        if (is_string($value)) {
            $value = Json::decode($value);
        }

        if ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        }

        if (is_array($value)) {
            return $value;
        }

        throw new RuntimeException('Expected array, '.Str::typeOf($value).' ('.Str::valueOf($value).') given.');
    }
}
