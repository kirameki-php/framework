<?php

namespace Stan\Kirameki\Extensions;

use Kirameki\Support\Sequence;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;

class SequenceFlatsReturnType implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Sequence::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'flatten'
            || $methodReflection->getName() === 'flatMap';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type
    {
        $classType = $scope->getType($methodCall->var);

        $intType = new IntegerType();
        $mixedType = new MixedType();

        $genericType = new GenericObjectType($classType->getClassName(), [$intType, $mixedType]);

        return new StaticType($genericType->getClassReflection());
    }
}
