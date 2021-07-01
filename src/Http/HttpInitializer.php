<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;

class HttpInitializer implements InitializerInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(HttpHandler::class, fn() => new HttpHandler());
    }
}
