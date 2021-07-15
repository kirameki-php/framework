<?php declare(strict_types=1);

namespace Kirameki\Http\Exceptions;

use Kirameki\Http\StatusCode;
use Throwable;

class UnsupportedMediaTypeException extends HttpException
{
    /**
     * @var string[]
     */
    public array $mediaTypes;

    /**
     * @param string[] $mediaTypes
     * @param Throwable|null $previous
     */
    public function __construct(array $mediaTypes, Throwable $previous = null)
    {
        $this->mediaTypes = $mediaTypes;

        $code = StatusCode::UnsupportedMediaType;
        $mediaTypesString = implode(', ', $mediaTypes);
        parent::__construct("[$mediaTypesString] could not be processed.", $code, $previous);
    }
}
