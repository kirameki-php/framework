<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Http\Exceptions\ValidationException;
use Kirameki\Http\Request\Validations\ValidationInterface;
use Kirameki\Support\Arr;
use Kirameki\Support\String\Str;
use ReflectionAttribute;
use ReflectionProperty;
use function array_key_exists;
use function class_exists;
use function filter_var;
use function is_array;

class FieldReflection
{
    /**
     * @var ReflectionProperty
     */
    protected ReflectionProperty $property;

    /**
     * @var Input
     */
    protected Input $definition;

    /**
     * @param Input $definition
     * @param ReflectionProperty $reflection
     */
    public function __construct(Input $definition, ReflectionProperty $reflection)
    {
        $this->property = $reflection;
        $this->definition = $definition;
    }

    /**
     * @param array $inputs
     * @throws ValidationException
     */
    public function validate(array $inputs)
    {
        if (!array_key_exists($this->definition->name, $inputs)) {
            if ($this->definition->required) {
                throw new ValidationException('Missing required field: ' . $this->definition->name);
            }
            return;
        }

        $attributes = $this->property->getAttributes(ValidationInterface::class);

        $validations = Arr::map($attributes, function(ReflectionAttribute $attribute) {
            return $attribute->newInstance();
        });

        Arr::each($validations, function(ValidationInterface $validation) use ($inputs) {
            $validation->validate($this->definition->name, $inputs);
        });
    }

    /**
     * @param object $object
     * @param mixed $value
     * @return void
     */
    public function inject(object $object, mixed $value): void
    {
        $this->property->setValue($object, $this->cast($value));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function cast(mixed $value): mixed
    {
        $type = $this->property->getType();
        $typeName = $type?->getName();
        $nullable = $type?->allowsNull() ?? true;
        return $this->castToType($typeName, $value, $nullable);
    }

    /**
     * @param string|null $type
     * @param mixed $value
     * @param bool $nullable
     * @return mixed
     */
    protected function castToType(?string $type, mixed $value, bool $nullable): mixed
    {
        if ($type === null) {
            return $value;
        }

        if ($type === 'string') {
            return (string) $value;
        }

        if ($nullable && $value === null) {
            return null;
        }

        if ($type === 'int') {
            if (($result = filter_var($value, FILTER_VALIDATE_INT)) === false) {
                $this->throwValidationException($type, $value);
            }
            return $result;
        }

        if ($type === 'float') {
            if (($result = filter_var($value, FILTER_VALIDATE_FLOAT)) === false) {
                $this->throwValidationException($type, $value);
            }
            return $result;
        }

        if ($type === 'bool') {
            if (($result = filter_var($value, FILTER_VALIDATE_BOOL)) === false) {
                $this->throwValidationException($type, $value);
            }
            return $result;
        }

        if ($type === 'array' && is_array($value)) {
            $arrayOf = Arr::first($this->property->getAttributes(ArrayOf::class))?->newInstance();
            if ($arrayOf instanceof ArrayOf) {
                $arr = [];
                foreach ($value as $key => $item) {
                    $arr[$key] = $this->castToType($arrayOf->type, $arrayOf->nullable, $item);
                }
                return $arr;
            } else {
                return $value;
            }
        }

        if ($type === 'object' && is_array($value)) {
            return (object) $value;
        }

        if (class_exists($type) && is_array($value)) {
            return FieldMap::instance($type, $value);
        }

        $this->throwValidationException($type, $value);
    }

    /**
     * @param string $expected
     * @param mixed $actual
     * @return never-return
     */
    protected function throwValidationException(string $expected, mixed $actual)
    {
        $valueAsString = Str::valueOf($actual);
        throw new ValidationException(("Expected value to be $expected. $valueAsString given."));
    }
}
