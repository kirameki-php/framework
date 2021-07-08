<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Http\Request;
use Kirameki\Support\Arr;
use ReflectionClass;

abstract class RequestDataAutoFill
{
    /**
     * @param array $inputs
     * @param Request $request
     */
    public function __construct(array $inputs, Request $request)
    {
        $data = Arr::mergeRecursive(
            $inputs,
            $request->url->queryParameters(),
        );

        $classReflection = new ReflectionClass(static::class);
        foreach ($classReflection->getProperties() as $propertyReflection) {
            $field = new RequestField($propertyReflection);
            $field->assignValue($data, $this);
        }
    }
}
