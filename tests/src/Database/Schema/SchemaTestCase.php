<?php

namespace Tests\Kirameki\Database\Schema;

use Kirameki\Database\Schema\Builders\CreateTableBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class SchemaTestCase extends DatabaseTestCase
{
    protected string $connection;

    protected function createTableBuilder(string $table)
    {
        return new CreateTableBuilder($this->mysqlConnection($this->connection), $table);
    }
}
