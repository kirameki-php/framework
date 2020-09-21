<?php

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection\Connection;
use Kirameki\Database\Query\Statements\InsertStatement;
use Traversable;

class InsertBuilder extends Builder
{
    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->statement = new InsertStatement;
    }

    /**
     * @param array $data
     */
    public function value(array $data)
    {
        $this->statement->dataset = [$data];
    }

    /**
     * @param iterable $dataset
     */
    public function values(iterable $dataset)
    {
        $dataset = ($dataset instanceof Traversable) ? iterator_to_array($dataset) : $dataset;
        $this->statement->dataset = $dataset;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!empty($this->statement->dataset)) {
            $formatter = $this->connection->getQueryFormatter();
            $statement = $formatter->statementForInsert($this->statement);
            $bindings = $formatter->bindingsForInsert($this->statement);
            $this->connection->affectingQuery($statement, $bindings);
        }
    }
}
