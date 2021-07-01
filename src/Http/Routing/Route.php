<?php declare(strict_types=1);

namespace Kirameki\Http\Routing;

use Attribute;
use Closure;
use Kirameki\Support\Arr;
use ReflectionAttribute;
use ReflectionMethod;
use RuntimeException;
use function str_replace;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    /**
     * @var string[]
     */
    public array $methods;

    /**
     * @var string
     */
    public string $path;

    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string|Closure|null
     */
    public string|Closure|null $action;

    /**
     * @param ReflectionAttribute $reflection
     * @param ReflectionMethod $reference
     * @return static
     */
    public static function fromReflection(ReflectionAttribute $reflection, ReflectionMethod $reference): static
    {
        $route = $reflection->newInstance();

        if ($route instanceof static) {
            $route->action = $reference->class.'::'.$reference->name;
            return $route;
        }

        throw new RuntimeException('Invalid class: '.$route::class.' from reflection. '.static::class.' expected.');
    }

    /**
     * @param array|string $method
     * @param string $path
     * @param string|null $name
     */
    public function __construct(array|string $method, string $path, ?string $name = null)
    {
        $this->methods = Arr::wrap($method);
        $this->path = $path;
        $this->name = $name ?? str_replace('/', '.', $path);
    }
}
