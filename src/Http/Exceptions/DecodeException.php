<?php declare(strict_types=1);

namespace Kirameki\Http\Exceptions;

use Throwable;

class DecodeException extends BadRequestException
{
    /**
     * @var string
     */
    public string $content;

    /**
     * @param string $content
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $content, $message = "", Throwable $previous = null)
    {
        $this->content = $content;
        parent::__construct($message, $previous);
    }
}
