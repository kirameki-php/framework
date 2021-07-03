<?php declare(strict_types=1);

namespace Kirameki\Http;

use Closure;
use Kirameki\Container\Container;
use Kirameki\Core\Application;
use Kirameki\Core\Config;
use Kirameki\Http\Codecs\Decoders\DecoderInterface;
use Kirameki\Http\Routing\Router;
use Kirameki\Support\Arr;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use function array_shift;
use function explode;
use function krsort;
use function str_starts_with;
use function substr;


class HttpHandler
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var Router
     */
    protected Router $router;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var Container
     */
    protected Container $mediaDecoders;

    /**
     * @var Container
     */
    protected Container $mediaEncoders;


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
        $this->mediaDecoders = new Container();
        $this->mediaEncoders = new Container();
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
     * @param string|array $mediaType
     * @param callable $resolver
     */
    public function registerEncoder(string|array $mediaType, callable $resolver)
    {
        foreach (Arr::wrap($mediaType) as $type) {
            $this->mediaEncoders->singleton($type, $resolver);
        }
    }

    /**
     * @param string|array $mediaType
     * @param callable $resolver
     * @return void
     */
    public function registerDecoder(string|array $mediaType, callable $resolver): void
    {
        foreach (Arr::wrap($mediaType) as $type) {
            $this->mediaDecoders->singleton($type, $resolver);
        }
    }

    /**
     * @param Request $request
     * @return DecoderInterface
     */
    protected function getDecoder(Request $request): DecoderInterface
    {
        foreach ($this->extractMediaTypesFromRequest($request) as $type) {
            if ($this->mediaDecoders->has($type)) {
                return $this->mediaDecoders->get($type);
            }
        }
        return $this->mediaDecoders->get('*/*');
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function extractMediaTypesFromRequest(Request $request): array
    {
        $typesByWeight = [];
        $segments = explode(',', $request->headers->get('Content-Type') ?? '');
        foreach($segments as $segment) {
            $parts = explode(';', $segment);
            $mediaType = array_shift($parts);
            $weight = 1.0;
            foreach($parts as $part) {
                if(str_starts_with($part, 'q=')) {
                    $weight = (float) substr($part, 2);
                    break;
                }
            }
            $typesByWeight[$weight][]= $mediaType;
        }
        krsort($typesByWeight);

        return Arr::flatten($typesByWeight);
    }

    /**
     * @param string $mediaType
     * @return DecoderInterface|null
     */
    protected function getEncoder(string $mediaType): ?DecoderInterface
    {

    }

    /**
     * @param Request $request
     * @param string|Closure $action
     * @return Response
     */
    protected function runAction(Request $request, string|Closure $action): Response
    {
        $function = $this->convertActionToClosure($request, $action);
        $arguments = [];
        $functionReflection = new ReflectionFunction($function);
        foreach ($functionReflection->getParameters() as $parameterReflection) {
            $paramName = $parameterReflection->getName();
            $typeReflection = $parameterReflection->getType();
            if ($typeReflection instanceof ReflectionNamedType) {
                $paramClass = $typeReflection->getName();
                $arguments[$paramName] = $this->app->get($paramClass);
            }
        }

        return $function(...$arguments);
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

        return (new ReflectionClass($class))
            ->getMethod($method)
            ->getClosure(new $class($request));
    }
}
