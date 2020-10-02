<?php

namespace Kirameki\Event;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Psr\Log\LoggerInterface;

class EventInitializer implements InitializerInterface
{
    public function register(Application $app): void
    {
        $app->singleton(EventManager::class, function () {
            return new EventManager();
        });
    }
}
