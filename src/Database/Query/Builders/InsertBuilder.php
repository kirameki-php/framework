<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\InsertStatement;
use Traversable;

class InsertBuilder extends StatementBuilder
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
     * @return $this
     */
    public function value(array $data): static
    {
        $this->statement->dataset = [$data];
        return $this;
    }

    /**
     * @param iterable $dataset
     * @return $this
     */
    public function values(iterable $dataset): static
    {
        $dataset = ($dataset instanceof Traversable) ? iterator_to_array($dataset) : $dataset;
        $this->statement->dataset = $dataset;
        return $this;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!empty($this->statement->dataset)) {
            $formatter = $this->connection->getQueryFormatter();
            $statement = $formatter->insertStatement($this->statement);
            $bindings = $formatter->insertBindings($this->statement);
            $this->connection->affectingQuery($statement, $bindings);
        }
    }

    /**
     * @return array
     */
    public function inspect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->insertStatement($this->statement);
        $bindings = $formatter->insertBindings($this->statement);
        return compact('statement', 'bindings');
    }
}
