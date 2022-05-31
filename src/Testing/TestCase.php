<?php declare(strict_types=1);

namespace Kirameki\Testing;

use Closure;
use Kirameki\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    /**
     * @var array<Closure>
     */
    private array $beforeSetupCallbacks = [];

    /**
     * @var array<Closure>
     */
    private array $afterSetupCallbacks = [];

    /**
     * @var array<Closure>
     */
    private array $beforeTearDownCallbacks = [];

    /**
     * @var array<Closure>
     */
    private array $afterTearDownCallbacks = [];

    /**
     * @private
     * @before
     */
    protected function setUpApplication(): void
    {
        $this->app = new Application(__DIR__ . '/../../tests');
    }

    protected function runBeforeSetup(Closure $callback): void
    {
        $this->beforeSetupCallbacks[] = $callback;
    }

    protected function runAfterSetup(Closure $callback): void
    {
        $this->afterSetupCallbacks[] = $callback;
    }

    protected function runBeforeTearDown(Closure $callback): void
    {
        $this->beforeTearDownCallbacks[]= $callback;
    }

    protected function runAfterTearDown(Closure $callback): void
    {
        $this->afterTearDownCallbacks[]= $callback;
    }

    protected function setUp(): void
    {
        array_map(static fn(Closure $callback) => $callback(), $this->beforeSetupCallbacks);
        parent::setUp();
        array_map(static fn(Closure $callback) => $callback(), $this->afterSetupCallbacks);
    }

    protected function tearDown(): void
    {
        array_map(static fn(Closure $callback) => $callback(), $this->beforeTearDownCallbacks);
        parent::tearDown();
        array_map(static fn(Closure $callback) => $callback(), $this->afterTearDownCallbacks);
    }
}
