<?php

namespace Kirameki\Exception;

use Kirameki\Support\Util;
use LogicException;

class ReturnValueException extends LogicException
{
    /**
     * @var mixed
     */
    public mixed $returnValue;

    /**
     * @param mixed $returnValue
     * @param string $message
     */
    public function __construct(mixed $returnValue, string $message)
    {
        $this->returnValue = $returnValue;
        $returnValueAsString = Util::toString($returnValue);
        parent::__construct(rtrim("Invalid return value: $returnValueAsString. ".$message));
    }
}
