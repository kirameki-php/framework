<?php declare(strict_types=1);

namespace Kirameki\Testing;

use Kirameki\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected static bool $booted = false;

    protected Application $app;

    /**
     * @before
     */
    protected function setUpApplication(): void
    {
        if (!static::$booted) $this->beforeBoot();

        $this->app = new Application(__DIR__ . '/../../tests');

        if (!static::$booted) $this->afterBoot();

        static::$booted = true;

        register_shutdown_function(fn() => $this->beforeShutdown());
    }

    protected function beforeBoot(): void
    {
    }

    protected function afterBoot(): void
    {
    }

    protected function beforeShutdown(): void
    {
    }
}
