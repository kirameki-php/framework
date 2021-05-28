<?php

namespace Kirameki\Exception;

use Kirameki\Support\Util;
use LogicException;

class InvalidKeyException extends LogicException
{
    /**
     * @param mixed $value
     */
    public function __construct(mixed $value)
    {
        $type = Util::typeOf($value);
        $valueAsString = Util::toString($value);
        parent::__construct("Key for array must be a string or int. $type given. (value: $valueAsString)");
    }
}
