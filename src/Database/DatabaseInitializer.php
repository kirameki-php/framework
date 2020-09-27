<?php

namespace Kirameki\Database;

use Kirameki\Core\Application;
use Psr\Log\LoggerInterface;

class DatabaseInitializer
{
    public function register(Application $app): void
    {
        $app->singleton(DatabaseManager::class, function () {
            return new DatabaseManager();
        });
    }
}
