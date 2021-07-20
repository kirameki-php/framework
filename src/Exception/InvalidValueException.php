<?php declare(strict_types=1);

namespace Kirameki\Exception;

use Kirameki\Support\String\Str;
use LogicException;

class InvalidValueException extends LogicException
{
    /**
     * @var mixed
     */
    public mixed $value;

    /**
     * @param mixed $value
     * @param string $expected
     */
    public function __construct(string $expected, mixed $value)
    {
        $this->value = $value;
        $valueAsString = Str::valueOf($value);
        parent::__construct("Expected value to be $expected. $valueAsString given.");
    }
}
