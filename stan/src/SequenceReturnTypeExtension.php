<?php

namespace Stan\Kirameki;

use Kirameki\Support\Sequence;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\TypeSpecifierAwareExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use function in_array;

class SequenceReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Sequence::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'keys',
            'map',
            'newInstance',
            'values',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type
    {
        $calledOnType = $scope->getType($methodCall->var);
        $firstArg = $methodReflection->getVariants()[0];
        $keyType = $firstArg->getTemplateTypeMap()->getType('TKey');
        $valueType = $firstArg->getTemplateTypeMap()->getType('TValue');

        $genericObjectType = new GenericObjectType($calledOnType->getClassName(), [$keyType, $valueType]);

        return new StaticType($genericObjectType->getClassReflection());
    }
}
