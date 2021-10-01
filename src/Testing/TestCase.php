<?php declare(strict_types=1);

namespace Kirameki\Testing;

use Kirameki\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    private array $beforeSetupCallbacks = [];
    private array $afterSetupCallbacks = [];

    private array $beforeTearDownCallbacks = [];
    private array $afterTearDownCallbacks = [];

    /**
     * @private
     * @before
     */
    protected function setUpApplication(): void
    {
        $this->app = new Application(__DIR__ . '/../../tests');
    }

    protected function runBeforeSetup(callable $callback): void
    {
        $this->beforeSetupCallbacks[] = $callback;
    }

    protected function runAfterSetup(callable $callback): void
    {
        $this->afterSetupCallbacks[] = $callback;
    }

    protected function runBeforeTearDown(callable $callback): void
    {
        $this->beforeTearDownCallbacks[]= $callback;
    }

    protected function runAfterTearDown(callable $callback): void
    {
        $this->afterTearDownCallbacks[]= $callback;
    }

    protected function setUp(): void
    {
        array_map(static fn($callback) => $callback(), $this->beforeSetupCallbacks);
        parent::setUp();
        array_map(static fn($callback) => $callback(), $this->afterSetupCallbacks);
    }

    protected function tearDown(): void
    {
        array_map(static fn($callback) => $callback(), $this->beforeTearDownCallbacks);
        parent::tearDown();
        array_map(static fn($callback) => $callback(), $this->afterTearDownCallbacks);
    }
}
