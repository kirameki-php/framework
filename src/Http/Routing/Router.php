<?php declare(strict_types=1);

namespace Kirameki\Http\Routing;

use Kirameki\Support\Arr;
use Kirameki\Support\File;
use Kirameki\Support\FileInfo;
use Kirameki\Support\Json;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use function getcwd;
use function preg_quote;
use function preg_replace;
use function str_replace;
use function str_starts_with;

class Router
{
    public function __construct()
    {
    }

    public function scanForRoutes(string $path)
    {
        $composer = Json::decodeFile(getcwd().'/composer.json');
        $autoloads = Arr::get($composer, 'autoload.psr-4');
        foreach ($autoloads as $namespace => $startPath) {
            if (str_starts_with($path, $startPath)) {
                $startPattern = '/^'.preg_quote($startPath, '/').'/';
            }
        }

        if (!isset($startPattern)) {
            throw new RuntimeException('No namespace matched for directory: '.$path);
        }

        $targetClasses = File::list($path, true)
            ->map(fn(FileInfo $file) => str_replace(getcwd().'/', '', $file->path()))
            ->map(fn(string $path) => preg_replace($startPattern, $namespace, $path, 1))
            ->map(fn(string $path) => preg_replace("/.php$/", '', $path))
            ->map(fn(string $path) => str_replace('/', '\\', $path));

        return $targetClasses
            ->flatMap(fn(string $class) => (new ReflectionClass($class))->getMethods())
            ->flatMap(fn(ReflectionMethod $method) => $method->getAttributes(Route::class))
            ->map(fn(ReflectionAttribute $attribute) => $attribute->newInstance())
            ->compact();
    }

    public function register(array|string $methods, string $path, string $name = null)
    {

    }
}
