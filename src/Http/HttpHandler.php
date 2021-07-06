<?php declare(strict_types=1);

namespace Kirameki\Http;

use Closure;
use Kirameki\Container\Container;
use Kirameki\Core\Application;
use Kirameki\Core\Config;
use Kirameki\Http\Request\Input;
use Kirameki\Http\Codecs\Decoders\DecoderInterface;
use Kirameki\Http\Exceptions\BadRequestException;
use Kirameki\Http\Request\RequestData;
use Kirameki\Http\Request\RequestField;
use Kirameki\Http\Routing\Router;
use Kirameki\Http\Request\Validations\Required;
use Kirameki\Http\Request\Validations\ValidationInterface;
use Kirameki\Support\Arr;
use Kirameki\Support\Assert;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;
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
     * @param string $mediaType
     * @return DecoderInterface|null
     */
    protected function getEncoder(string $mediaType): ?DecoderInterface
    {

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
     * @param string $contentType
     * @return DecoderInterface
     */
    protected function getDecoder(string $contentType): DecoderInterface
    {
        foreach ($this->extractMediaTypesFromRequest($contentType) as $type) {
            if ($this->mediaDecoders->has($type)) {
                return $this->mediaDecoders->get($type);
            }
        }
        return $this->mediaDecoders->get('*/*');
    }

    /**
     * @param string $contentType
     * @return array
     */
    protected function extractMediaTypesFromRequest(string $contentType): array
    {
        $typesByWeight = [];
        $segments = explode(',', $contentType);
        foreach ($segments as $segment) {
            $parts = explode(';', $segment);
            $mediaType = array_shift($parts);
            $weight = 1.0;
            foreach ($parts as $part) {
                if (str_starts_with($part, 'q=')) {
                    $weight = (float)substr($part, 2);
                    break;
                }
            }
            $typesByWeight[$weight][] = $mediaType;
        }
        krsort($typesByWeight);

        return Arr::flatten($typesByWeight);
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
     * @return RequestData
     */
    protected function createRequestData(Request $request, Closure $action): RequestData
    {
        $contentType = $request->headers->get('Content-Type') ?? '';
        $mediaDecoder = $this->getDecoder($contentType);

        try {
            $inputs = Arr::mergeRecursive(
                $request->url->queryParameters(),
                $mediaDecoder->decode($request->body)
            );
        } catch (Throwable $throwable) {
            throw new BadRequestException(previous: $throwable);
        }

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

        Assert::isClassOf($targetClass, RequestData::class);

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
        $fields = [];
        foreach ($classReflection->getProperties() as $propertyReflection) {
            $field = $fields[] = new RequestField($propertyReflection);
            foreach ($propertyReflection->getAttributes() as $attributeReflection) {
                $attribute = $attributeReflection->newInstance();
                if ($attribute instanceof Input) {
                    $field->name = $attribute->name;
                } elseif($attribute instanceof Required) {
                    $field->required = true;
                } elseif ($attribute instanceof ValidationInterface) {
                    $field->validations[] = $attribute;
                }
            }
        }

        foreach ($fields as $field) {
            if (!array_key_exists($field->name, $inputs)) {
                if ($field->required) {
                    throw new BadRequestException('Missing required field: '.$field->name);
                }
                continue;
            }

            foreach ($field->validations as $validation) {
                $validation->validate($field->name, $inputs);
            }

            $field->setValue($data, $inputs[$field->name]);
        }
    }
}
