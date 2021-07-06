<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Http\Request\Validations\ValidationInterface;
use ReflectionProperty;

class RequestField
{
    /**
     * @var ReflectionProperty
     */
    public ReflectionProperty $propertyReflection;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var bool
     */
    public bool $required;

    /**
     * @var ValidationInterface[]
     */
    public array $validations;

    /**
     * @param ReflectionProperty $reflectionProperty
     */
    public function __construct(ReflectionProperty $reflectionProperty)
    {
        $this->propertyReflection = $reflectionProperty;
        $this->name = $reflectionProperty->getName();
        $this->required = false;
        $this->validations = [];
    }

    /**
     * @param object $accessor
     * @param mixed $value
     */
    public function setValue(object $accessor, mixed $value)
    {
        $this->propertyReflection->setValue($accessor, $value);
    }
}
