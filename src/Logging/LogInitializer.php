<?php

namespace Kirameki\Logging;

use Kirameki\Application;
use Psr\Log\LoggerInterface;

class LogInitializer
{
    public function register(Application $app): void
    {
        $logger = new LogManager();
        $logger->setLogger('file', new Writers\FileWriter);
        $logger->setDefaultLogger('file');
        $app->singleton(LoggerInterface::class, $logger);
    }
}
