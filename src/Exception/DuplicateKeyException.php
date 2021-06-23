<?php declare(strict_types=1);

namespace Kirameki\Exception;

use LogicException;

class DuplicateKeyException extends LogicException
{
    /**
     * @var string|int
     */
    public string|int $key;

    /**
     * @var mixed
     */
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
