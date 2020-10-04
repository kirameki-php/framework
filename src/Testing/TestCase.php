<?php declare(strict_types=1);

namespace Kirameki\Testing;

use Kirameki\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static bool $booted = false;

    protected Application $app;

    /**
     * @before
     */
    private function setUpApplication(): void
    {
        if (!static::$booted) $this->beforeBoot();

        $this->app = new Application(__DIR__ . '/../../tests');

        if (!static::$booted) $this->afterBoot();

        static::$booted = true;

        register_shutdown_function(fn() => $this->beforeShutdown());
    }

    abstract protected function beforeBoot(): void;

    abstract protected function afterBoot(): void;

    abstract protected function beforeShutdown(): void;
}
