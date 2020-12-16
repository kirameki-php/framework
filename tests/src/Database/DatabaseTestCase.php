<?php declare(strict_types=1);

namespace Kirameki\Tests\Database;

use Kirameki\Database\Connection;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;
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

    public function createTable(string $connection, string $table): CreateTableBuilder
    {
        return new CreateTableBuilder($this->connection($connection), $table);
    }
}
