<?php declare(strict_types=1);

namespace Kirameki\Tests;

use Kirameki\Core\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var Application
     */
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__.'/..', __DIR__.'/..');
    }
}
