<?php

namespace Kirameki\Model;

use Kirameki\Model\Casts\CastInterface;

class Property
{
    public string $name;

    public CastInterface $cast;

    public $default;

    public function __construct(string $name, CastInterface $cast, $default)
    {
        $this->name = $name;
        $this->cast = $cast;
        $this->default = $default;
    }
}
