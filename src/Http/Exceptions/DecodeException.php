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
     * @param string $media
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct(string $media, $message = "", Throwable $previous = null)
    {
        $this->content = $media;
        parent::__construct($message, $previous);
    }
}
