<?php declare(strict_types=1);

namespace Kirameki\Http\Response;

use Kirameki\Http\StatusCode;

class ResponseBuilder
{
    /**
     * @var int
     */
    public int $statusCode;

    /**
     * @var ResponseHeaders
     */
    public ResponseHeaders $headers;

    /**
     * @var mixed
     */
    public mixed $data;

    /**
     * @param ResponseHeaders|null $headers
     */
    public function __construct(ResponseHeaders $headers = null)
    {
        $this->statusCode = StatusCode::OK;
        $this->headers = $headers ?? new ResponseHeaders();
        $this->data = null;
    }
}
