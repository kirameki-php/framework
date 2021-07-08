<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Http\Exceptions\ValidationException;
use Kirameki\Http\Request\Validations\Optional;
use Kirameki\Http\Request\Validations\ValidationInterface;
use Kirameki\Support\Util;
use ReflectionProperty;

class RequestField
{
    /**
     * @var ReflectionProperty
     */
    protected ReflectionProperty $propertyReflection;

    /**
     * @var string
     */
    public string $inputName;

    /**
     * @var bool
     */
    protected bool $required;

    /**
     * @var ValidationInterface[]
     */
    protected array $validations;

    /**
     * @param ReflectionProperty $reflectionProperty
     */
    public function __construct(ReflectionProperty $reflectionProperty)
    {
        $this->propertyReflection = $reflectionProperty;
        $this->inputName = $reflectionProperty->getName();
        $this->required = true;
        $this->validations = [];

        foreach ($reflectionProperty->getAttributes() as $attributeReflection) {
            $attribute = $attributeReflection->newInstance();
            if ($attribute instanceof Input) {
                $this->inputName = $attribute->name;
            } elseif ($attribute instanceof Optional) {
                $this->required = false;
            } elseif ($attribute instanceof ValidationInterface) {
                $this->validations[] = $attribute;
            }
        }
    }

    /**
     * @param array $inputs
     * @param object $target
     * @return void
     */
    public function assignValue(array $inputs, object $target)
    {
        if (!$this->inputExists($inputs)) {
            return;
        }

        $this->runValidations($inputs);

        $casted = $this->castToType($inputs[$this->inputName]);

        $this->propertyReflection->setValue($target, $casted);
    }

    /**
     * @param array $inputs
     * @return bool
     */
    protected function inputExists(array $inputs): bool
    {
        if (array_key_exists($this->inputName, $inputs)) {
            return true;
        }

        if ($this->required) {
            throw new ValidationException('Missing required field: ' . $this->inputName);
        }

        return false;
    }

    /**
     * @param array $inputs
     * @return void
     */
    protected function runValidations(array $inputs)
    {
        foreach ($this->validations as $validation) {
            $validation->validate($this->inputName, $inputs);
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function castToType(mixed $value): mixed
    {
        $type = $this->propertyReflection->getType();
        $typeName = $type?->getName();

        if ($typeName === null) {
            return $value;
        }

        if ($type->allowsNull() && $value === null) {
            return null;
        }

        if ($typeName === 'string') {
            return (string) $value;
        }

        if ($typeName === 'int') {
            if (($result = filter_var($value, FILTER_VALIDATE_INT)) === false) {
                $this->throwValidationException($typeName, $value);
            }
            return $result;
        }

        if ($typeName === 'float') {
            if (($result = filter_var($value, FILTER_VALIDATE_FLOAT)) === false) {
                $this->throwValidationException($typeName, $value);
            }
            return $result;
        }

        if ($typeName === 'bool') {
            if (($result = filter_var($value, FILTER_VALIDATE_BOOL)) === false) {
                $this->throwValidationException($typeName, $value);
            }
            return $result;
        }

        if ($typeName === 'array' && is_array($value)) {
            return $value;
        }

        if ($typeName === 'object' && is_array($value)) {
            return (object) $value;
        }

        if (class_exists($typeName)) {
            return new $typeName($value);
        }

        $this->throwValidationException($typeName, $value);
    }

    /**
     * @param string $expected
     * @param mixed $actual
     * @return void
     */
    protected function throwValidationException(string $expected, $actual)
    {
        $valueAsString = Util::toString($actual);
        throw new ValidationException(("Expected value to be $expected. $valueAsString given."));
    }
}
