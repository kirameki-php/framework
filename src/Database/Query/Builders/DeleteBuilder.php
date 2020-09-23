<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\DeleteStatement;

class DeleteBuilder extends ConditonBuilder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new DeleteStatement;
    }

    /**
     * @return int
     */
    public function execute()
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->statementForDelete($this->statement);
        $bindings = $formatter->bindingsForDelete($this->statement);
        return $this->connection->affectingQuery($statement, $bindings);
    }

    /**
     * @return array
     */
    public function inspect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->statementForDelete($this->statement);
        $bindings = $formatter->bindingsForDelete($this->statement);
        return compact('statement', 'bindings');
    }
}
