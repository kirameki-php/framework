<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Kirameki\Http\Codecs\Decoders\JsonDecoder;
use Kirameki\Http\Codecs\Decoders\NullDecoder;
use Kirameki\Http\Routing\Router;

class HttpInitializer implements InitializerInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(HttpHandler::class, function (Application $app) {
            $router = $app->get(Router::class);
            $config = $app->config('http');
            $handler = new HttpHandler($app, $router, $config);

            $handler->registerEncoder('application/x-www-form-urlencoded', fn() => new NullDecoder);
            $handler->registerEncoder('application/json', fn() => new JsonDecoder);

            return $handler;
        });

        $app->singleton(Router::class, fn() => new Router());
    }
}
