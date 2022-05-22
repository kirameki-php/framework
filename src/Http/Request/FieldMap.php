<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Support\Arr;
use ReflectionClass;
use function array_key_exists;

class FieldMap
{
    public ReflectionClass $class;

    /**
     * @var FieldReflection[]
     */
    public array $fields;

    /**
     * @param class-string $class
     * @param array $data
     * @return object
     */
    public static function instance(string $class, array $data): object
    {
        return (new static($class))->newInstance($data);
    }

    /**
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = new ReflectionClass($class);
        $this->fields = [];
        foreach ($this->class->getProperties() as $prop) {
            $input = Arr::firstOr($prop->getAttributes(Input::class), null)?->newInstance();
            if ($input instanceof Input) {
                $input->name ??= $prop->name;
                $this->fields[$input->name] = new FieldReflection($input, $prop);
            }
        }
    }

    /**
     * @param array $data
     * @return object
     */
    public function newInstance(array $data): object
    {
        $instance = $this->class->newInstance();

        foreach ($this->fields as $name => $field) {
            if (array_key_exists($name, $data)) {
                $field->inject($instance, $data[$name]);
            }
        }

        return $instance;
    }
}
