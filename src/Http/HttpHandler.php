<?php declare(strict_types=1);

namespace Kirameki\Http;

use Closure;
use Kirameki\Core\Application;
use Kirameki\Core\Config;
use Kirameki\Http\Exceptions\BadRequestException;
use Kirameki\Http\Exceptions\ValidationException;
use Kirameki\Http\Request\RequestData;
use Kirameki\Http\Request\RequestField;
use Kirameki\Http\Routing\Router;
use Kirameki\Support\Arr;
use Kirameki\Support\Assert;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;
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
        $route = $this->router->findMatch($request->method, $request->url->path());
        $responsable = $this->runAction($request, $route->action);

        return $responsable;
    }

    /**
     * @param Request $request
     * @param string|Closure $action
     * @return Response
     */
    protected function runAction(Request $request, string|Closure $action): Response
    {
        $function = $this->convertActionToClosure($request, $action);
        $request->data = $this->createRequestData($request, $function);
        return $function($request->data);
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
        // if no Content-Type is defined, use application/octet-stream
        // @see https://datatracker.ietf.org/doc/html/rfc7231#section-3.1.1.5
        $contentType = $request->headers->get('Content-Type') ?: 'application/octet-stream';

        $inputs = Arr::mergeRecursive(
            $request->url->queryParameters(),
            $this->codecs->decode($contentType, $request->body),
        );

        $targetClass = $this->resolveDataClass($action);
        $data = new $targetClass($inputs);

        if (!($data instanceof RequestData)) {
            $this->injectIntoProperties($data, $inputs);
        }

        return $data;
    }

    /**
     * @param Closure $action
     * @return string
     */
    protected function resolveDataClass(Closure $action): string
    {
        $targetClass = RequestData::class;

        $functionReflection = new ReflectionFunction($action);
        $parameterReflection = Arr::first($functionReflection->getParameters());
        if ($typeReflection = $parameterReflection?->getType()) {
            if ($typeReflection instanceof ReflectionNamedType) {
                $targetClass = $typeReflection->getName();
            }
        }

        Assert::isClass($targetClass);

        return $targetClass;
    }

    /**
     * @param object $data
     * @param array $inputs
     * @return void
     */
    protected function injectIntoProperties(object $data, array $inputs): void
    {
        $classReflection = new ReflectionClass($data);
        foreach ($classReflection->getProperties() as $propertyReflection) {
            $field = new RequestField($propertyReflection);
            $field->assignValue($inputs, $data);
        }
    }
}
