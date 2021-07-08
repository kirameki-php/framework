<?php declare(strict_types=1);

namespace Kirameki\Http\Exceptions;

use Throwable;

class UnsupportedMediaTypeException extends HttpException
{
    /**
     * @var string[]
     */
    public array $contentTypes;

    /**
     * @param string[] $contentTypes
     * @param Throwable|null $previous
     */
    public function __construct(array $contentTypes, Throwable $previous = null)
    {
        $this->contentTypes = $contentTypes;
        $contentTypes = implode(', ', $contentTypes);
        parent::__construct("[$contentTypes] could not be processed.", 415, $previous);
    }
}
