<?php declare(strict_types=1);

namespace Kirameki\Http\Request\Casts;

use RuntimeException;

class FloatArray extends ArrayObject
{
    /**
     * @param array $array
     */
    public function __construct(array $array)
    {
        foreach ($array as $key => $value) {
            $castedValue = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($castedValue === false) {
                throw new RuntimeException("$value could not be casted to int");
            }
            $this->array[$key] = $castedValue;
        }
    }
}
