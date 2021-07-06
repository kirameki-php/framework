<?php declare(strict_types=1);

namespace Kirameki\Http\Request\Casts;

class IntArray
{
    /**
     * @var int[]
     */
    public array $array = [];

    /**
     * @param array $array
     */
    public function __construct(array $array)
    {
        foreach ($array as $key => $value) {
            $this->array[$key] = $value;
        }
    }
}
