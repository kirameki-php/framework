<?php

namespace Kirameki\Tests\Database;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Database\Support\Expr;
use Kirameki\Tests\Database\DatabaseTestCase;
use RuntimeException;

class ConnectionTest extends DatabaseTestCase
{
    protected string $connection = 'mysql';

    protected function createDummyTable()
    {
        $connection = $this->connection($this->connection);
        $schema = $this->createTable($this->connection, 'Dummy');
        $schema->uuid('id')->primaryKey()->notNull();
        foreach ($schema->toDdls() as $ddl) {
            $connection->executeSchema($ddl);
        }
    }

    public function testTableExists()
    {
        $this->createDummyTable();
        $connection = $this->connection($this->connection);

        self::assertTrue($connection->tableExists('Dummy'));
    }

}
