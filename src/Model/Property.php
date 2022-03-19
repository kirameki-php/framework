<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Model\Casts\Cast;

class Property
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var Cast
     */
    public Cast $cast;

    /**
     * @var mixed
     */
    public mixed $default;

    /**
     * @param string $name
     * @param Cast $cast
     * @param mixed $default
     */
    public function __construct(string $name, Cast $cast, mixed $default)
    {
        $this->name = $name;
        $this->cast = $cast;
        $this->default = $default;
    }
}
