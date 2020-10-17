<?php

namespace Kirameki\Logging;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;

class LogInitializer implements InitializerInterface
{
    public function register(Application $app): void
    {
        $config = $app->config()->dig('logging');
        $logger = new LogManager($config);
        $logger->setLogger('file', fn($options) => new Loggers\FileLogger($options));
        $logger->setLogger('stdout', fn($options) => new Loggers\StdoutLogger($options));
        $logger->setDefaultChannel($config->get('default') ?? 'file');
        $app->singleton(LogManager::class, $logger);
    }
}
