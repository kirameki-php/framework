<?php

namespace Kirameki\Exception;

use Kirameki\Support\Arr;
use LogicException;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

class MethodArgumentException extends LogicException
{
    /**
     * @param int $argIndex
     * @param string $message
     * @param Throwable|null $previous
     * @throws ReflectionException
     */
    public function __construct(int $argIndex, string $message, Throwable $previous = null)
    {
        $traces = debug_backtrace(limit: 2);
        $trace = end($traces);
        $object = $trace['object'];
        $class = get_class($object);
        $method = $trace['function'] ?? '<Unknown>';

        $ref = new ReflectionMethod($trace['object'], $method);
        $params = Arr::map($ref->getParameters(), fn(ReflectionParameter $p) => $p->name);
        $message = rtrim("[$class::$method] \$$params[$argIndex] ".$message);
        parent::__construct($message, 0, $previous);
    }
}
