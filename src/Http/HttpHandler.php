<?php declare(strict_types=1);

namespace Kirameki\Http;

use Closure;
use Kirameki\Core\Application;
use Kirameki\Core\Config;
use Kirameki\Http\Request\RequestData;
use Kirameki\Http\Routing\Router;
use Kirameki\Support\Arr;
use Kirameki\Support\Assert;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use function explode;

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
     * @var ContentCodecs
     */
    public ContentCodecs $codecs;

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
        $this->codecs = new ContentCodecs;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function process(Request $request): Response
    {
        $route = $this->router->findMatch($request->method, $request->url->path());

        $actionFunction = $this->convertActionToClosure($request, $route->action);

        $request->data = $this->createRequestData($request, $actionFunction);

        return $actionFunction($request->data);
    }

    /**
     * @param Request $request
     * @param string|Closure $action
     * @return Closure
     */
    protected function convertActionToClosure(Request $request, string|Closure $action): Closure
    {
        if ($action instanceof Closure) {
            return $action;
        }

        [$class, $method] = explode('::', $action, 2);

        Assert::isClassOf($class, Controller::class);

        return (new ReflectionClass($class))
            ->getMethod($method)
            ->getClosure(new $class($request));
    }

    /**
     * @param Request $request
     * @param Closure $action
     * @return object
     */
    protected function createRequestData(Request $request, Closure $action): object
    {
        $dataClass = RequestData::class;
        $functionReflection = new ReflectionFunction($action);
        $parameterReflection = Arr::first($functionReflection->getParameters());
        if ($typeReflection = $parameterReflection?->getType()) {
            if ($typeReflection instanceof ReflectionNamedType) {
                $dataClass = $typeReflection->getName();
            }
        }

        // if no Content-Type is defined, use application/octet-stream
        // @see https://datatracker.ietf.org/doc/html/rfc7231#section-3.1.1.5
        $contentType = $request->headers->get('Content-Type') ?: 'application/octet-stream';
        $inputs = $this->codecs->decode($contentType, $request->body);

        return new $dataClass($inputs, $request);
    }
}
