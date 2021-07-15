<?php declare(strict_types=1);

namespace Kirameki\Http\Response;

use Kirameki\Http\Codecs\Codecs;
use Kirameki\Http\Request;
use Kirameki\Http\Response;
use Kirameki\Http\StatusCode;

class ResponseBuilder
{
    /**
     * @var Request
     */
    public Request $request;

    /**
     * @var Codecs
     */
    public Codecs $codecs;

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
     * @param Request $request
     * @param Codecs $codecs
     * @param ResponseHeaders|null $headers
     */
    public function __construct(Request $request, Codecs $codecs, ResponseHeaders $headers = null)
    {
        $this->request = $request;
        $this->codecs = $codecs;
        $this->statusCode = $code ?? StatusCode::OK;
        $this->headers = $headers ?? new ResponseHeaders();
        $this->data = null;
    }

    /**
     * @return Response
     */
    public function build(): Response
    {
        $contentType = 'application/octet-stream';

        $data = ($this->data instanceof ResponseData)
            ? $this->data
            : null;

        $contentType = $data->getContentType();
        $contentType ??= $this->request->headers->get('Accept') ?: 'application/octet-stream';

        return new Response(
            body: $this->codecs->encode($contentType, $data),
            code: $this->statusCode,
            headers: $this->headers,
            version: $this->request->httpVersion(),
        );
    }
}
