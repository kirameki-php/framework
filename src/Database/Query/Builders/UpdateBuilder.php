<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\UpdateStatement;

class UpdateBuilder extends ConditonsBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new UpdateStatement;
    }

    /**
     * @param array $assignments
     * @return $this
     */
    public function set(array $assignments)
    {
        $this->statement->data = $assignments;
        return $this;
    }

    /**
     * @return int
     */
    public function execute()
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->statementForUpdate($this->statement);
        $bindings = $formatter->bindingsForUpdate($this->statement);
        return $this->connection->affectingQuery($statement, $bindings);
    }

    /**
     * @return array
     */
    public function inspect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->statementForUpdate($this->statement);
        $bindings = $formatter->bindingsForUpdate($this->statement);
        return compact('statement', 'bindings');
    }
}
