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
use PHPStan\Type\IntegerType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeSpecifierAwareExtension;

class SequenceKeysReturnType implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Sequence::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'keys';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type
    {
        $classType = $scope->getType($methodCall->var);

        $intType = new IntegerType();
        $keyType = $classType->getClassReflection()->getTemplateTypeMap()->getType('TKey');

        $genericType = new GenericObjectType($classType->getClassName(), [$intType, $keyType]);

        return new StaticType($genericType->getClassReflection());
    }
}
