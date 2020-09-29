<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\BaseStatement;

abstract class StatementBuilder
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
     * @param string|null $as
     * @return $this
     */
    public function table(string $table, ?string $as = null)
    {
        $this->statement->table = $table;
        $this->statement->tableAlias = $as;
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
     * @return array
     */
    abstract public function inspect(): array;

    /**
     * @return string
     */
    public function toSql(): string
    {
        $formatter = $this->connection->getQueryFormatter();
        return $formatter->interpolate(...array_values($this->inspect()));
    }
}
