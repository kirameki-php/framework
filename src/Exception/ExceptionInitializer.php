<?php declare(strict_types=1);

namespace Kirameki\Exception;

use Kirameki\Core\Application;
use Kirameki\Core\Initializer;
use Kirameki\Exception\Handlers\LogHandler;
use Kirameki\Exception\Handlers\VarDumpHandler;

class ExceptionInitializer implements Initializer
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $manager = new ExceptionManager;
        $app->singleton(ExceptionManager::class, $manager);
        $manager->setHandler('log', fn() => new LogHandler);
        $manager->setHandler('dump', fn() => new VarDumpHandler);
    }
}
