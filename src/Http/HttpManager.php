<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Handlers\HandlerInterface;

class HttpManager
{
    /**
     * @var HandlerInterface[]
     */
    protected array $contentHandlers = [];

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
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
     * @param HandlerInterface $handler
     * @return void
     */
    public function addContentHandler(HandlerInterface $handler)
    {
        foreach ($handler->getContentTypes() as $type) {
            $this->contentHandlers[$type] = $handler;
        }
    }

    /**
     * @param string $contentType
     * @return HandlerInterface|null
     */
    public function getContentHandler(string $contentType): ?HandlerInterface
    {
        return $this->contentHandlers[$contentType] ?? null;
    }
}
