<?php

namespace Tests\Kirameki\Database\Query;

use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
use Tests\Kirameki\Database\DatabaseTestCase;

class QueryTestCase extends DatabaseTestCase
{
    protected string $connection;

    protected function selectBuilder()
    {
        return new SelectBuilder($this->mysqlConnection($this->connection));
    }

    protected function insertBuilder()
    {
        return new InsertBuilder($this->mysqlConnection($this->connection));
    }

    protected function updateBuilder()
    {
        return new UpdateBuilder($this->mysqlConnection($this->connection));
    }

    protected function deleteBuilder()
    {
        return new DeleteBuilder($this->mysqlConnection($this->connection));
    }
}
