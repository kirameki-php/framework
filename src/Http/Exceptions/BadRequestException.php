<?php declare(strict_types=1);

namespace Kirameki\Http\Exceptions;

use Throwable;

class BadRequestException extends HttpException
{
    public function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}
