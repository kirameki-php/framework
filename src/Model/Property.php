<?php declare(strict_types=1);

namespace Kirameki\Model;

use Kirameki\Model\Casts\CastInterface;

class Property
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var CastInterface
     */
    public CastInterface $cast;

    /**
     * @var mixed
     */
    public mixed $default;

    /**
     * @param string $name
     * @param CastInterface $cast
     * @param mixed $default
     */
    public function __construct(string $name, CastInterface $cast, mixed $default)
    {
        $this->name = $name;
        $this->cast = $cast;
        $this->default = $default;
    }
}
