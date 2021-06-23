<?php declare(strict_types=1);

namespace Kirameki\Database\Support;

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
    public static function raw(string $value): static
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
    public function toString(): string
    {
        return (string) $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
