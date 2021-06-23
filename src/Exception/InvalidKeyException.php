<?php declare(strict_types=1);

namespace Kirameki\Exception;

use Kirameki\Support\Util;
use LogicException;

class InvalidKeyException extends LogicException
{
    /**
     * @param mixed $key
     */
    public function __construct(mixed $key)
    {
        $type = Util::typeOf($key);
        $keyAsString = Util::toString($key);
        parent::__construct("Key for array must be a string or int. $type given. (value: $keyAsString)");
    }
}
