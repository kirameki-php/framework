<?php

namespace Kirameki\Database;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Kirameki\Event\EventManager;
use Psr\Log\LoggerInterface;

class DatabaseInitializer implements InitializerInterface
{
    public function register(Application $app): void
    {
        $app->singleton(DatabaseManager::class, function (Application $app) {
            return new DatabaseManager($app->get(EventManager::class));
        });
    }
}
