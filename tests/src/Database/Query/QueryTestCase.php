<?php

namespace Kirameki\Tests\Database\Query;

use Kirameki\Database\Adapters\SqliteAdapter;
use Kirameki\Database\Connection;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Tests\TestCase;

class QueryTestCase extends TestCase
{
    protected function makeConnection()
    {
        return new Connection('test', new SqliteAdapter([]));
    }

    protected function selectBuilder()
    {
        return new SelectBuilder($this->makeConnection());
    }

    protected function insertBuilder()
    {
        return new InsertBuilder($this->makeConnection());
    }
}
