<?php

namespace Kirameki\Exceptions;

use Error;
use ReflectionProperty;

class FatalError extends Error
{
    private array $error;

    /**
     * @param array $error An array as returned by error_get_last()
     * @param int $code (default: 0)
     */
    public function __construct(array $error, int $code = 0)
    {
        parent::__construct($error['message'], $code);
        $this->error = $error;

        foreach (['file', 'line'] as $name) {
            $property = new ReflectionProperty(Error::class, $name);
            $property->setAccessible(true);
            $property->setValue($this, $error[$name]);
        }
    }

    public function getError(): array
    {
        return $this->error;
    }
}
