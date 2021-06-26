<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Connection;
use Kirameki\Database\Query\Statements\UpdateStatement;

class UpdateBuilder extends ConditionsBuilder
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
    public function set(array $assignments): static
    {
        $this->statement->data = $assignments;
        return $this;
    }

    /**
     * @return int
     */
    public function execute(): int
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->updateStatement($this->statement);
        $bindings = $formatter->updateBindings($this->statement);
        return $this->connection->affectingQuery($statement, $bindings);
    }

    /**
     * @return array
     */
    public function inspect(): array
    {
        $formatter = $this->connection->getQueryFormatter();
        $statement = $formatter->updateStatement($this->statement);
        $bindings = $formatter->updateBindings($this->statement);
        return compact('statement', 'bindings');
    }
}
