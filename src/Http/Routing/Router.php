<?php declare(strict_types=1);

namespace Kirameki\Http\Routing;

use Kirameki\Http\Request;
use Kirameki\Support\Arr;
use Kirameki\Support\Collection;
use Kirameki\Support\File;
use Kirameki\Support\FileInfo;
use Kirameki\Support\Json;
use ReflectionClass;
use RuntimeException;
use function getcwd;
use function preg_quote;
use function preg_replace;
use function str_replace;
use function str_starts_with;

class Router
{
    /**
     * @var array
     */
    protected array $routesByName = [];

    /**
     * @param string $path
     */
    protected function scanForRoutesIn(string $path)
    {
        $cwd = getcwd();

        $composer = Json::decodeFile($cwd.'/composer.json');
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
            ->map(fn(FileInfo $file) => str_replace($cwd.'/', '', $file->path()))
            ->map(fn(string $path) => preg_replace($startingPattern, $startingNamespace, $path, 1))
            ->map(fn(string $path) => preg_replace("/.php$/", '', $path))
            ->map(fn(string $path) => str_replace('/', '\\', $path));

        $targetClasses->each(function(string $class) {
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

    /**
     * @param string $method
     * @param string $path
     * @return Route
     */
    public function findMatch(string $method, string $path): Route
    {
        $this->scanForRoutesIn('app/Http/Controllers');
        return $this->getRoutes()->first();
    }
}
