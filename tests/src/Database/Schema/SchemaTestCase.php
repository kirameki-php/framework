<?php

namespace Kirameki\Tests\Database\Schema;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Kirameki\Tests\Database\DatabaseTestCase;

class SchemaTestCase extends DatabaseTestCase
{
    protected string $connection;

    protected function createTableBuilder(string $table)
    {
        return new CreateTableBuilder($this->connection($this->connection), $table);
    }
}
