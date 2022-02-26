<?php declare(strict_types=1);

namespace Kirameki\Logging;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;
use Kirameki\Support\Arr;

class LogInitializer implements InitializerInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $config = $app->config()->for('logging');
        $manager = new LogManager($config);
        $manager->addLogger('file', fn($opt) => new Loggers\FileLogger($opt));
        $manager->addLogger('multi', fn($opt) => new Loggers\MultiLogger(Arr::map($opt['channels'], fn($c) => $manager->channel($c))));
        $manager->addLogger('null', fn() => new Loggers\NullLogger);
        $manager->addLogger('stdout', fn($opt) => new Loggers\StdoutLogger($opt));
        $manager->setDefaultChannel($config->getString('default'));
        $app->singleton(LogManager::class, $manager);
    }
}
