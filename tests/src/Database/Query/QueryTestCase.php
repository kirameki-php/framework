<?php

namespace Kirameki\Tests\Database\Query;

use Kirameki\Database\Connection\SqliteConnection;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Tests\TestCase;

class QueryTestCase extends TestCase
{
    protected function makeConnection()
    {
        return new SqliteConnection('test', []);
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
