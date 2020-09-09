<?php

namespace Kirameki\Logging;

use Kirameki\Application;
use Psr\Log\LoggerInterface;

class LogInitializer
{
    public function register(Application $app): void
    {
        $logger = new LogManager();
        $logger->addLogger('file', new FileLogger);
        $logger->setDefaultLogger('file');
        $app->singleton(LoggerInterface::class, $logger);
    }
}
