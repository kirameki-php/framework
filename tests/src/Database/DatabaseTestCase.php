<?php declare(strict_types=1);

namespace Kirameki\Tests\Database;

use Kirameki\Database\Connection;
use Kirameki\Tests\TestCase;

class DatabaseTestCase extends TestCase
{
    /**
     * @param string $name
     * @return Connection
     */
    public function connection(string $name): Connection
    {
        return db()->using($name);
    }
}
