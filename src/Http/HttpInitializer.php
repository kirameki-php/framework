<?php

namespace Kirameki\Http;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Kirameki\Support\Arr;

class HttpInitializer implements InitializerInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(HttpManager::class, fn() => new HttpManager());
    }
}
