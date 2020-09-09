<?php

namespace Kirameki\Exceptions;

use Kirameki\Application;
use Kirameki\Exceptions\Handlers\LogHandler;

class ExceptionInitializer
{
    public function register(Application $app): void
    {
        $manager = new ExceptionManager;
        $app->singleton(ExceptionManager::class, $manager);
        $manager->setHandler('log', fn() => new LogHandler);
    }
}
