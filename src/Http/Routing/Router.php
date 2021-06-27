<?php declare(strict_types=1);

namespace Kirameki\Http\Routing;

class Router
{
    public function __construct()
    {
    }

    public function scanForRoutes(string $path)
    {
        scandir($path, SCANDIR_SORT_NONE);
    }

    public function register(array|string $methods, string $path, string $name = null)
    {

    }
}
