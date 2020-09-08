<?php

namespace Kirameki\Logging;

use Kirameki\Application;
use Psr\Log\LoggerInterface;

class LogInitializer
{
    public function register(Application $container)
    {
        $container->singleton(LoggerInterface::class, static function () {
            return new LogManager();
        });
    }
}
