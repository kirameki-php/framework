<?php declare(strict_types=1);

namespace Kirameki\Exception;

use Kirameki\Support\Str;
use LogicException;

class InvalidKeyException extends LogicException
{
    /**
     * @param mixed $key
     */
    public function __construct(mixed $key)
    {
        $type = Str::typeOf($key);
        $keyAsString = Str::valueOf($key);
        parent::__construct("Key for array must be a string or int. $type given. (value: $keyAsString)");
    }
}
