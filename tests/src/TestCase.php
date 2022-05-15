<?php declare(strict_types=1);

namespace Tests\Kirameki;

use Kirameki\Testing\TestCase as BaseTestCase;
use function getenv;

class TestCase extends BaseTestCase
{
    protected function includeSlowTests(): bool
    {
        return (bool) getenv('INCLUDE_SLOW_TESTS');
    }
}
