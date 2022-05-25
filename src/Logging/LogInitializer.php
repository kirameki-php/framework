<?php declare(strict_types=1);

namespace Kirameki\Logging;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;
use function array_map;

class LogInitializer implements Initializer
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
        $manager->addLogger('multi', fn($opt) => new Loggers\MultiLogger(array_map(static fn($c) => $manager->channel($c), $opt['channels'])));
        $manager->addLogger('null', fn() => new Loggers\NullLogger);
        $manager->addLogger('stdout', fn($opt) => new Loggers\StdoutLogger($opt));
        $manager->setDefaultChannel($config->getString('default'));
        $app->singleton(LogManager::class, $manager);
    }
}
