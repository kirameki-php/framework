<?php declare(strict_types=1);

namespace Kirameki\Http\Routing;

use Kirameki\Support\Arr;
use Kirameki\Support\Collection;
use Kirameki\Support\File;
use Kirameki\Support\FileInfo;
use Kirameki\Support\Json;
use ReflectionClass;
use RuntimeException;
use function preg_quote;
use function preg_replace;
use function str_replace;

class Router
{
    /**
     * @var array
     */
    protected array $routesByName = [];

    /**
     * @param string $path
     * @return Collection|FileInfo[]
     */
    public function scanForRoutes(string $path): Collection
    {
        $composer = Json::decodeFile(getcwd().'/composer.json');
        $autoloads = Arr::get($composer, 'autoload.psr-4');

        $startingNamespace = null;
        $startingPattern = null;
        foreach ($autoloads as $namespace => $startPath) {
            if (str_starts_with($path, $startPath)) {
                $startingNamespace = $namespace;
                $startingPattern = '/^'.preg_quote($startPath, '/').'/';
            }
        }

        if (!isset($startingPattern)) {
            throw new RuntimeException('No namespace matched for directory: '.$path);
        }

        $targetClasses = File::list($path, true)
            ->map(fn(FileInfo $file) => str_replace(getcwd().'/', '', $file->path()))
            ->map(fn(string $path) => preg_replace($startingPattern, $startingNamespace, $path, 1))
            ->map(fn(string $path) => preg_replace("/.php$/", '', $path))
            ->map(fn(string $path) => str_replace('/', '\\', $path));

        return $targetClasses->flatMap(function(string $class) {
            $reflectionClass = new ReflectionClass($class);
            foreach ($reflectionClass->getMethods() as $methodReflection) {
                foreach ($methodReflection->getAttributes(Route::class) as $attributeReflection) {
                    $route = Route::fromReflection($attributeReflection, $methodReflection);
                    $this->register($route);
                }
            }
        });
    }

    /**
     * @param Route $route
     * @return void
     */
    public function register(Route $route)
    {
        $this->routesByName[$route->name] = $route;
    }

    /**
     * @return Collection<Route>
     */
    public function getRoutes(): Collection
    {
        return new Collection($this->routesByName);
    }
}
