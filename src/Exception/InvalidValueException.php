<?php

namespace Kirameki\Exception;

use Kirameki\Support\Util;
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
        $valueAsString = Util::toString($value);
        parent::__construct(rtrim("Expected value to be $expected. $valueAsString given."));
    }
}
