<?php declare(strict_types=1);

namespace Kirameki\Event;

use Kirameki\Core\Application;
use Kirameki\Core\InitializerInterface;

class EventInitializer implements InitializerInterface
{
    /**
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(EventManager::class, function () {
            return new EventManager();
        });
    }
}
