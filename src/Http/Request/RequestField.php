<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Exception\InvalidValueException;
use Kirameki\Http\Exceptions\BadRequestException;
use Kirameki\Http\Request\Validations\Optional;
use Kirameki\Http\Request\Validations\ValidationInterface;
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

    protected function inputExists(array $inputs)
    {
        if (array_key_exists($this->inputName, $inputs)) {
            return true;
        }

        if ($this->required) {
            throw new BadRequestException('Missing required field: ' . $this->inputName);
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
        try {
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
                    throw new InvalidValueException($typeName, $value);
                }
                return $result;
            }

            if ($typeName === 'float') {
                if (($result = filter_var($value, FILTER_VALIDATE_FLOAT)) === false) {
                    throw new InvalidValueException($typeName, $value);
                }
                return $result;
            }

            if ($typeName === 'bool') {
                if (($result = filter_var($value, FILTER_VALIDATE_BOOL)) === false) {
                    throw new InvalidValueException($typeName, $value);
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

            throw new InvalidValueException($typeName, $value);
        }
        catch (InvalidValueException $exception) {
            throw new BadRequestException($exception->getMessage(), $exception);
        }
    }
}
