<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Http\Exceptions\ValidationException;
use Kirameki\Http\Request\Validations\Validation;
use ArrayAccess;
use Kirameki\Support\Arr;
use Kirameki\Support\Str;
use ReflectionAttribute;
use ReflectionProperty;
use function array_key_exists;
use function array_map;
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
    public function validate(array $inputs): void
    {
        if (!array_key_exists($this->definition->name, $inputs)) {
            if ($this->definition->required) {
                throw new ValidationException('Missing required field: ' . $this->definition->name);
            }
            return;
        }

        $attributes = $this->property->getAttributes(Validation::class);

        $validations = array_map(
            static fn(ReflectionAttribute $attribute): Validation => $attribute->newInstance(),
            $attributes
        );

        Arr::each($validations, function(Validation $validation) use ($inputs) {
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

        if (is_array($value)) {
            if ($type === 'array' || is_subclass_of($type, ArrayAccess::class)) {
                $list = $type === 'array' ? [] : new $type;
                if (($arrType = $this->definition->arrayOf) !== null) {
                    foreach ($value as $key => $item) {
                        $list[$key] = $this->castToType($arrType, $item, true);
                    }
                    return $list;
                }
                return $value;
            }

            if ($type === 'object') {
                return (object) $value;
            }

            if (class_exists($type)) {
                return FieldMap::instance($type, $value);
            }
        }

        $this->throwValidationException($type, $value);
    }

    /**
     * @param string $expected
     * @param mixed $actual
     * @return never-return
     */
    protected function throwValidationException(string $expected, mixed $actual): void
    {
        $valueAsString = Str::valueOf($actual);
        throw new ValidationException(("Expected value to be $expected. $valueAsString given."));
    }
}
