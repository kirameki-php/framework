<?php

namespace Kirameki\Tests\Database\Query;

use Kirameki\Database\Query\Builders\DeleteBuilder;
use Kirameki\Database\Query\Builders\InsertBuilder;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Builders\UpdateBuilder;
use Kirameki\Tests\Database\DatabaseTestCase;

class QueryTestCase extends DatabaseTestCase
{
    protected string $connection;

    protected function selectBuilder()
    {
        return new SelectBuilder($this->connection($this->connection));
    }

    protected function insertBuilder()
    {
        return new InsertBuilder($this->connection($this->connection));
    }

    protected function updateBuilder()
    {
        return new UpdateBuilder($this->connection($this->connection));
    }

    protected function deleteBuilder()
    {
        return new DeleteBuilder($this->connection($this->connection));
    }
}
