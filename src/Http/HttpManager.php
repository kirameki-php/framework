<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Codecs\CodecInterface;

class HttpManager
{
    /**
     * @var CodecInterface[]
     */
    protected array $contentHandlers = [];

    /**
     * @param Request $request
     * @return Response
     */
    public function process(Request $request): Response
    {
        $response = new Response($request);
        return $response;
    }

    /**
     * @return void
     */
    public function send(Response $response)
    {
        if (!headers_sent()) {
            $code = $response->statusCode;

            header($response->statusLine(), true, $code);

            foreach ($response->headers->all() as $name => $value) {
                header($name.': '.$value, true, $code);
            }

            header('Content-Length: '.strlen($response->body), true, $code);
        }

        echo $response->body;
    }

    /**
     * @param CodecInterface $handler
     * @return void
     */
    public function addContentHandler(CodecInterface $handler)
    {
        foreach ($handler->getContentTypes() as $type) {
            $this->contentHandlers[$type] = $handler;
        }
    }

    /**
     * @param string $contentType
     * @return CodecInterface|null
     */
    public function getContentHandler(string $contentType): ?CodecInterface
    {
        return $this->contentHandlers[$contentType] ?? null;
    }
}
