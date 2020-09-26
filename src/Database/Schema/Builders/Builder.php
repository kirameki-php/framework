<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\BaseStatement;

abstract class Builder
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var BaseStatement
     */
    protected $statement;

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->statement = clone $this->statement;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table(string $table)
    {
        $this->statement->table = $table;
        return $this;
    }

    /**
     * @return static
     */
    protected function copy()
    {
        return clone $this;
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $formatter = $this->connection->getQueryFormatter();
        return $formatter->interpolate($this->statement);
    }
}
