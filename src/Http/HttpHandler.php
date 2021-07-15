<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Core\Application;
use Kirameki\Core\Config;
use Kirameki\Http\Codecs\Codecs;
use Kirameki\Http\Request\RequestData;
use Kirameki\Http\Response\ResponseBuilder;
use Kirameki\Http\Response\ResponseData;
use Kirameki\Http\Routing\Router;
use Kirameki\Support\Assert;
use Kirameki\Support\Util;
use RuntimeException;
use function explode;
use function is_string;

class HttpHandler
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @psalm-readonly
     * @var Router
     */
    public Router $router;

    /**
     * @psalm-readonly
     * @var Config
     */
    public Config $config;

    /**
     * @psalm-readonly
     * @var Codecs
     */
    public Codecs $codecs;

    /**
     * @param Application $app
     * @param Router $router
     * @param Config $config
     */
    public function __construct(Application $app, Router $router, Config $config)
    {
        $this->app = $app;
        $this->router = $router;
        $this->config = $config;
        $this->codecs = new Codecs;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function process(Request $request): Response
    {
        $request->data->merge($this->decodeInput($request));

        $route = $this->router->findMatch($request->method, $request->url->path());

        [$class, $method] = explode('::', $route->action, 2);

        $result = $this->makeController($class, $request)->runAction($method);

        return $this->buildResponse($request, $result);
    }

    /**
     * @param string $class
     * @param Request $request
     * @return Controller
     */
    protected function makeController(string $class, Request $request): Controller
    {
        Assert::isClassOf($class, Controller::class);

        return new $class($request, new ResponseBuilder($request, $this->codecs));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function decodeInput(Request $request): RequestData
    {
        // if no Content-Type is defined, use application/octet-stream
        // @see https://datatracker.ietf.org/doc/html/rfc7231#section-3.1.1.5
        $contentType = $request->headers->get('Content-Type') ?: 'application/octet-stream';

        return $this->codecs->decode($contentType, $request->body);
    }

    /**
     * @param Request $request
     * @param ResponseBuilder $builder
     * @return Response
     */
    protected function buildResponse(Request $request, ResponseBuilder $builder): Response
    {
        $contentType ??= $request->headers->get('Accept') ?: 'application/octet-stream';

        return new Response(
            body: $this->encodeDataToString($contentType, $builder->data),
            code: $builder->statusCode,
            headers: $builder->headers,
            version: $request->httpVersion(),
        );
    }

    /**
     * @param string $contentType
     * @param mixed $data
     * @return string
     */
    protected function encodeDataToString(string $contentType, mixed $data): string
    {
        if ($data instanceof ResponseData) {
            $contentType = $data->getContentType() ?? $contentType;
            return $this->codecs->encode($contentType, $data->jsonSerialize());
        }

        if (is_string($data)) {
            return $data;
        }

        throw new RuntimeException('Unknown response data type: '.Util::typeOf($data));
    }
}
