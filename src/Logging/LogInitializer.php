<?php

namespace Kirameki\Logging;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Psr\Log\LoggerInterface;

class LogInitializer implements InitializerInterface
{
    public function register(Application $app): void
    {
        $logger = new LogManager();
        $logger->setLogger('file', new Writers\FileWriter);
        $logger->setDefaultLogger('file');
        $app->singleton(LoggerInterface::class, $logger);
    }
}
