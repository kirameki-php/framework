<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Codecs\CodecInterface;

class HttpHandler
{
    /**
     * @var CodecInterface[]
     */
    protected array $codecs = [];

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
     * @param CodecInterface $codec
     * @return void
     */
    public function addCodec(CodecInterface $codec)
    {
        foreach ($codec->getContentTypes() as $type) {
            $this->codecs[$type] = $codec;
        }
    }

    /**
     * @param string $contentType
     * @return CodecInterface|null
     */
    public function getCodec(string $contentType): ?CodecInterface
    {
        return $this->codecs[$contentType] ?? null;
    }
}
