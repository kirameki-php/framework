<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Request\FieldMap;
use Kirameki\Http\Response\ResponseBuilder;
use Kirameki\Support\Arr;
use ReflectionMethod;
use ReflectionNamedType;

abstract class Controller
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var ResponseBuilder
     */
    protected ResponseBuilder $response;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->response = new ResponseBuilder();
    }

    /**
     * @param string $method
     * @return ResponseBuilder
     */
    public function runAction(string $method): ResponseBuilder
    {
        $data = $this->getActionArg($method);
        $this->response->data = $this->$method($data) ?? $this->response->data;
        return $this->response;
    }

    /**
     * @param string $method
     * @return object
     */
    protected function getActionArg(string $method): object
    {
        $reflection = new ReflectionMethod($this, $method);
        $parameterReflection = Arr::first($reflection->getParameters());
        if ($typeReflection = $parameterReflection?->getType()) {
            if ($typeReflection instanceof ReflectionNamedType) {
                $class = $typeReflection->getName();
                return FieldMap::instance($class, $this->request->data->all());
            }
        }
        return $this->request->data;
    }
}
