<?php

namespace Kirameki\Database\Query\Support;

class Expr
{
    /**
     * @var string
     */
    protected string $value;

    /**
     * @param string $value
     * @return static
     */
    public static function raw(string $value)
    {
        return new static($value);
    }

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return (string) $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
