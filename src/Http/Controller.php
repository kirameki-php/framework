<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Http\Request\RequestData;
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
     * @param ResponseBuilder $response
     */
    public function __construct(Request $request, ResponseBuilder $response)
    {
        $this->request = $request;
        $this->response = $response;
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
     * @return RequestData
     */
    protected function getActionArg(string $method): RequestData
    {
        $reflection = new ReflectionMethod($this, $method);
        $parameterReflection = Arr::first($reflection->getParameters());
        if ($typeReflection = $parameterReflection?->getType()) {
            if ($typeReflection instanceof ReflectionNamedType) {
                $dataClass = $typeReflection->getName();
            }
        }
        return $this->request->data;
    }
}
