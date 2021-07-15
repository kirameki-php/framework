<?php declare(strict_types=1);

namespace Kirameki\Http\Request;

use Kirameki\Support\Arr;
use ReflectionClass;
use function array_key_exists;

class RequestFields
{
    public ReflectionClass $class;

    /**
     * @var RequestField[]
     */
    public array $fields;

    /**
     * @param string $class
     * @return static
     */
    public static function for(string $class): static
    {
        return new static($class);
    }

    /**
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = new ReflectionClass($class);
        $this->fields = [];
        foreach ($this->class->getProperties() as $prop) {
            $input = Arr::first($prop->getAttributes(Input::class))?->newInstance();
            if ($input !== null && $input instanceof Input) {
                $input->name ??= $prop->name;
                $this->fields[$input->name] = new RequestField($input, $prop);
            }
        }
    }

    /**
     * @param array $inputs
     * @return object
     */
    public function newInstanceWith(array $inputs): object
    {
        $instance = $this->class->newInstance();
        foreach ($inputs as $name => $value) {
            if (array_key_exists($name, $this->fields)) {
                $this->fields[$name]->inject($instance, $value);
            }
        }
        return $instance;
    }
}
