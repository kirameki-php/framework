<?php

namespace Kirameki\Exception;

use Kirameki\Support\Util;
use LogicException;
use ReflectionException;
use ReflectionMethod;

class UnexpectedArgumentException extends LogicException
{
    /**
     * @param int $argNo
     * @param mixed $expected
     * @param mixed $actual
     * @throws ReflectionException
     */
    public function __construct(int $argNo, mixed $expected, mixed $actual)
    {
        $traces = debug_backtrace(limit: 2);
        $trace = end($traces);
        $object = $trace['object'];
        $class = get_class($object);
        $method = $trace['function'] ?? '<Unknown>';
        $paramName = (new ReflectionMethod($trace['object'], $method))->getParameters()[$argNo]->name;

        $identityAsString = sprintf('%s::%s() Argument #%s ($%s)', $class, $method, $argNo, $paramName);
        $expectedAsString = Util::toString($expected);
        $actualAsString = Util::toString($actual);

        parent::__construct("$identityAsString must be $expectedAsString, $actualAsString given.");
    }
}
