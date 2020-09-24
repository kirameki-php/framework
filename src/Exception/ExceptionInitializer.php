<?php

namespace Kirameki\Exception;

use Kirameki\Core\Application;
use Kirameki\Exception\Handlers\LogHandler;

class ExceptionInitializer
{
    public function register(Application $app): void
    {
        $manager = new ExceptionManager;
        $app->singleton(ExceptionManager::class, $manager);
        $manager->setHandler('log', fn() => new LogHandler);
    }
}
