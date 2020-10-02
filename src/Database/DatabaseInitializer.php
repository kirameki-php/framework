<?php

namespace Kirameki\Database;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Psr\Log\LoggerInterface;

class DatabaseInitializer implements InitializerInterface
{
    public function register(Application $app): void
    {
        $app->singleton(DatabaseManager::class, function () {
            return new DatabaseManager();
        });
    }
}
