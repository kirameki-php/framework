<?php

namespace Kirameki\Exception;

use LogicException;

class DuplicateKeyException extends LogicException
{
    public string|int $key;
    public mixed $value;

    /**
     * @param string|int $key
     * @param mixed $value
     */
    public function __construct(string|int $key, mixed $value)
    {
        $this->key = $key;
        $this->value = $value;
        parent::__construct("Tried to overwrite existing key: ".$key);
    }
}
